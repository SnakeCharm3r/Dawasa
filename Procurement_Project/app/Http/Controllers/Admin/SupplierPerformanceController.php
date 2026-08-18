<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GoodsReceiptNote;
use App\Models\Supplier;
use App\Models\SupplierPerformanceIncident;
use App\Services\EntityAccessService;
use App\Services\SupplierComplianceService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPerformanceController extends Controller
{
    public function __construct(
        private readonly SupplierComplianceService $compliance,
        private readonly SupplierPerformanceService $performance,
        private readonly EntityAccessService $entityAccess,
    ) {}

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        $this->readable($request);
        $entityId = $this->entityAccess->entityId($request, $request->user());
        $evaluationQuery = $supplier->performanceEvaluations()->with(['businessEntity:id,name', 'calculatedBy:id,name']);
        $incidentQuery = $supplier->performanceIncidents()->with(['purchaseOrder:id,purchase_order_number,business_entity_id', 'goodsReceiptNote:id,grn_number', 'recordedBy:id,name']);
        if ($entityId) {
            $evaluationQuery->where('business_entity_id', $entityId);
            $incidentQuery->where(fn ($query) => $query->whereNull('purchase_order_id')->orWhereHas('purchaseOrder', fn ($order) => $order->where('business_entity_id', $entityId)));
        }

        $current = (clone $evaluationQuery)->latest('calculated_at')->first();
        $history = $evaluationQuery->latest('calculated_at')->limit(24)->get();
        $incidents = $incidentQuery->latest('occurred_at')->limit(100)->get();
        $financial = $request->user()->hasAnyRole(['accountant', 'gm', 'ceo', 'auditor', 'super_admin']);
        if (! $financial) {
            $current?->makeHidden(['total_awarded_value', 'commercial_reliability_score']);
            $history->each->makeHidden(['total_awarded_value', 'commercial_reliability_score']);
        }

        return response()->json(['data' => [
            'supplier' => $supplier->only(['id', 'name', 'code', 'portal_status', 'compliance_status', 'award_eligibility', 'restriction_reason']),
            'compliance' => $this->compliance->assess($supplier),
            'current_evaluation' => $current,
            'evaluation_history' => $history,
            'incidents' => $incidents,
            'procurement_history' => $this->procurementHistory($supplier, $entityId, $financial),
            'activity' => ActivityLog::with('actor:id,name')->where('subject_type', Supplier::class)->where('subject_id', $supplier->id)->latest()->limit(100)->get(),
        ]]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $this->readable($request);
        $suppliers = Supplier::with('documents')->orderBy('name')->get()->map(function (Supplier $supplier) {
            $assessment = $this->compliance->assess($supplier);

            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'code' => $supplier->code,
                'portal_status' => $supplier->portal_status,
                'compliance_status' => $assessment['status'],
                'award_eligibility' => $assessment['award_eligibility'],
                'reason' => $assessment['reason'],
                'missing_documents' => $assessment['missing_documents'],
                'expired_documents' => $assessment['expired_documents'],
                'expiring_documents' => $assessment['expiring_documents'],
            ];
        })->filter(fn ($supplier) => $supplier['compliance_status'] !== 'complete')->values();

        return response()->json(['data' => $suppliers]);
    }

    public function calculate(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $entityId = $this->entityAccess->entityId($request, $request->user());
        $evaluation = $this->performance->calculate($supplier, $entityId, $request->user());
        ActivityLog::record($request->user(), 'supplier.performance_calculated', $supplier, [], ['evaluation_id' => $evaluation->id, 'grade' => $evaluation->grade]);

        return response()->json(['message' => 'Supplier performance snapshot calculated.', 'data' => $evaluation], 201);
    }

    public function incident(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'gm', 'ceo', 'accountant']), 403);
        $data = $request->validate([
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt_note_id' => ['nullable', 'integer', 'exists:goods_receipt_notes,id'],
            'supplier_invoice_id' => ['nullable', 'integer', 'exists:supplier_invoices,id'],
            'incident_type' => ['required', 'in:late_delivery,partial_delivery,rejected_goods,damaged_goods,quality_failure,missing_document,expired_document,invoice_variance,cancelled_po,complaint,other'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $incident = $this->performance->recordIncident($supplier, $data, $request->user());
        ActivityLog::record($request->user(), 'supplier.incident_recorded', $supplier, [], ['incident_id' => $incident->id, 'severity' => $incident->severity, 'incident_type' => $incident->incident_type]);

        return response()->json(['message' => 'Supplier performance incident recorded.', 'data' => $incident], 201);
    }

    public function resolveIncident(Request $request, SupplierPerformanceIncident $incident): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate(['resolution_notes' => ['required', 'string', 'max:5000']]);
        abort_if($incident->resolved_at, 422, 'This incident is already resolved.');
        $incident->update(['resolved_at' => now(), 'resolution_notes' => $data['resolution_notes']]);
        ActivityLog::record($request->user(), 'supplier.incident_resolved', $incident->supplier, [], ['incident_id' => $incident->id, 'resolution_notes' => $data['resolution_notes']]);

        return response()->json(['message' => 'Incident resolved.', 'data' => $incident->fresh()]);
    }

    public function override(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate([
            'eligibility' => ['required', 'in:eligible,restricted,blocked'],
            'reason' => ['required', 'string', 'max:5000'],
            'expires_at' => ['required', 'date', 'after:now'],
        ]);
        $override = $supplier->performanceOverrides()->create([...$data, 'created_by' => $request->user()->id]);
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.eligibility_override', $supplier, [], ['override_id' => $override->id, ...$data]);

        return response()->json(['message' => 'Temporary supplier eligibility override recorded.', 'data' => $override], 201);
    }

    private function readable(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'accountant', 'gm', 'ceo', 'auditor']), 403);
    }

    private function procurementHistory(Supplier $supplier, ?int $entityId, bool $financial): array
    {
        $orders = $supplier->purchaseOrders()->when($entityId, fn ($query) => $query->where('business_entity_id', $entityId))->with(['businessEntity:id,name', 'requisition:id,requisition_number', 'items'])->latest()->limit(100)->get();
        if (! $financial) {
            $orders->each->makeHidden(['subtotal', 'discount_amount', 'tax_amount', 'total_amount']);
        }

        return [
            'tender_responses' => $supplier->tenderResponses()->with('tender:id,tender_number,title,status')->latest()->limit(100)->get()->makeHidden($financial ? [] : ['subtotal', 'tax_amount', 'total_amount']),
            'purchase_orders' => $orders,
            'goods_receipts' => $supplier->purchaseOrders()->when($entityId, fn ($query) => $query->where('business_entity_id', $entityId))->with('items')->get()->flatMap(fn ($order) => $order->id ? GoodsReceiptNote::with('items')->where('purchase_order_id', $order->id)->get() : collect())->values(),
            'invoice_match_outcomes' => $supplier->invoices()->when($entityId, fn ($query) => $query->where('business_entity_id', $entityId))->with('matchRecords')->latest()->limit(100)->get()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'match_records' => $invoice->matchRecords->map(fn ($record) => $financial
                    ? $record
                    : $record->only(['id', 'match_status', 'matched_at'])),
            ]),
        ];
    }
}
