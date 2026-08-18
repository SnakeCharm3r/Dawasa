<?php

namespace App\Services;

use App\Models\GoodsReceiptNote;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPerformanceEvaluation;
use App\Models\SupplierPerformanceIncident;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    public const CALCULATION_VERSION = '1.0';

    public function __construct(private readonly SupplierComplianceService $compliance) {}

    public function calculate(Supplier $supplier, ?int $businessEntityId = null, ?User $actor = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): SupplierPerformanceEvaluation
    {
        $end ??= now();
        $start ??= $end->copy()->subYear()->startOfDay();

        $orders = $supplier->purchaseOrders()
            ->when($businessEntityId, fn ($query) => $query->where('business_entity_id', $businessEntityId))
            ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
            ->with(['items', 'supplierInvoices.matchRecords'])
            ->get();
        $completed = $orders->whereIn('status', [PurchaseOrder::STATUS_FULLY_RECEIVED, PurchaseOrder::STATUS_CLOSED]);
        $cancelled = $orders->where('status', PurchaseOrder::STATUS_CANCELLED);
        $incidents = $supplier->performanceIncidents()
            ->whereBetween('occurred_at', [$start, $end])
            ->get();

        $delivery = $this->deliveryScore($completed, $supplier->id);
        $quality = $this->qualityScore($supplier->id, $businessEntityId, $start, $end, $incidents);
        $compliance = $this->compliance->assess($supplier);
        $responsiveness = $this->responsivenessScore($supplier, $start, $end);
        $commercial = $this->commercialScore($orders, $incidents);
        $overall = round($delivery * .30 + $quality * .25 + $compliance['score'] * .20 + $responsiveness * .10 + $commercial * .15, 2);
        $grade = $completed->count() < 3 ? 'insufficient_data' : $this->grade($overall);

        return SupplierPerformanceEvaluation::create([
            'supplier_id' => $supplier->id,
            'business_entity_id' => $businessEntityId,
            'evaluation_period_start' => $start->toDateString(),
            'evaluation_period_end' => $end->toDateString(),
            'purchase_orders_count' => $orders->count(),
            'completed_purchase_orders_count' => $completed->count(),
            'cancelled_purchase_orders_count' => $cancelled->count(),
            'total_awarded_value' => $orders->sum(fn (PurchaseOrder $order) => (float) $order->total_amount),
            'delivery_score' => $delivery,
            'quality_score' => $quality,
            'compliance_score' => $compliance['score'],
            'responsiveness_score' => $responsiveness,
            'commercial_reliability_score' => $commercial,
            'overall_score' => $overall,
            'grade' => $grade,
            'calculated_at' => now(),
            'calculated_by' => $actor?->id,
            'calculation_version' => self::CALCULATION_VERSION,
            'notes' => 'Automatically calculated from procurement history.',
        ]);
    }

    public function recordIncident(Supplier $supplier, array $data, User $actor, bool $recalculate = true): SupplierPerformanceIncident
    {
        $incident = $supplier->performanceIncidents()->create([...$data, 'recorded_by' => $actor->id]);
        if ($recalculate && in_array($incident->severity, ['high', 'critical'], true)) {
            $businessEntityId = $incident->purchaseOrder?->business_entity_id;
            $this->calculate($supplier, $businessEntityId, $actor);
        }

        return $incident;
    }

    private function deliveryScore($completedOrders, int $supplierId): float
    {
        if ($completedOrders->isEmpty()) {
            return 100;
        }

        return round($completedOrders->avg(function (PurchaseOrder $order) use ($supplierId) {
            if (! $order->expected_delivery_date) {
                return 100;
            }
            $completion = GoodsReceiptNote::where('supplier_id', $supplierId)
                ->where('purchase_order_id', $order->id)
                ->whereIn('status', [GoodsReceiptNote::STATUS_ACCEPTED, GoodsReceiptNote::STATUS_PARTIALLY_ACCEPTED])
                ->max('received_date');
            if (! $completion) {
                return 50;
            }
            $daysLate = max(0, (int) ceil($order->expected_delivery_date->copy()->startOfDay()->diffInDays(Carbon::parse($completion)->startOfDay(), false)));

            return max(0, 100 - ($daysLate * 5));
        }), 2);
    }

    private function qualityScore(int $supplierId, ?int $businessEntityId, CarbonInterface $start, CarbonInterface $end, $incidents): float
    {
        $totals = DB::table('goods_receipt_note_items as items')
            ->join('goods_receipt_notes as grns', 'grns.id', '=', 'items.goods_receipt_note_id')
            ->where('grns.supplier_id', $supplierId)
            ->when($businessEntityId, fn ($query) => $query->where('grns.business_entity_id', $businessEntityId))
            ->whereBetween('grns.received_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(items.quantity_received), 0) received, COALESCE(SUM(items.quantity_accepted), 0) accepted')
            ->first();
        $score = (float) $totals->received > 0 ? ((float) $totals->accepted / (float) $totals->received) * 100 : 100;
        $penalty = $incidents->whereIn('incident_type', ['quality_failure', 'damaged_goods', 'rejected_goods', 'complaint'])
            ->sum(fn ($incident) => ['low' => 2, 'medium' => 5, 'high' => 12, 'critical' => 25][$incident->severity] ?? 0);

        return round(max(0, $score - $penalty), 2);
    }

    private function responsivenessScore(Supplier $supplier, CarbonInterface $start, CarbonInterface $end): float
    {
        $invitations = DB::table('tender_invitations as invitations')
            ->join('tenders', 'tenders.id', '=', 'invitations.tender_id')
            ->leftJoin('tender_responses as responses', function ($join) use ($supplier) {
                $join->on('responses.tender_id', '=', 'tenders.id')->where('responses.supplier_id', '=', $supplier->id);
            })
            ->where('invitations.supplier_id', $supplier->id)
            ->whereBetween('invitations.invited_at', [$start, $end])
            ->whereIn('tenders.supplier_category_id', $supplier->categories()->pluck('supplier_categories.id'))
            ->get(['invitations.invited_at', 'tenders.submission_deadline', 'responses.submitted_at']);
        if ($invitations->isEmpty()) {
            return 100;
        }

        return round($invitations->avg(function ($invitation) {
            if (! $invitation->submitted_at || $invitation->submitted_at > $invitation->submission_deadline) {
                return 0;
            }
            $window = max(1, Carbon::parse($invitation->invited_at)->diffInMinutes(Carbon::parse($invitation->submission_deadline)));
            $used = Carbon::parse($invitation->invited_at)->diffInMinutes(Carbon::parse($invitation->submitted_at));

            return max(60, 100 - (($used / $window) * 40));
        }), 2);
    }

    private function commercialScore($orders, $incidents): float
    {
        $varianceCount = $orders->flatMap->supplierInvoices->flatMap->matchRecords
            ->whereIn('match_status', ['quantity_variance', 'price_variance', 'failed'])->count();
        $incidentPenalty = $incidents->whereIn('incident_type', ['invoice_variance', 'cancelled_po', 'complaint', 'other'])
            ->sum(fn ($incident) => ['low' => 2, 'medium' => 5, 'high' => 12, 'critical' => 25][$incident->severity] ?? 0);

        return round(max(0, 100 - ($varianceCount * 8) - ($orders->where('status', PurchaseOrder::STATUS_CANCELLED)->count() * 15) - $incidentPenalty), 2);
    }

    private function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }
}
