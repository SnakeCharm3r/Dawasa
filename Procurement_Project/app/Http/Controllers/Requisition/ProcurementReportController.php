<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Models\BudgetTransaction;
use App\Models\GoodsReceiptNote;
use App\Models\PaymentVoucher;
use App\Models\ProcurementClosure;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\RequisitionApproval;
use App\Models\SupplierInvoice;
use App\Models\SupplierQuotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementReportController extends Controller
{
    protected function applyEntityFilter($query, Request $request)
    {
        if ($request->has('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }
        if ($request->has('financial_year_id')) {
            $query->where('financial_year_id', $request->input('financial_year_id'));
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        return $query;
    }

    public function requisitionRegister(Request $request): JsonResponse
    {
        $query = PurchaseRequisition::with(['requester', 'department', 'businessEntity', 'purchaseOrder']);
        $this->applyEntityFilter($query, $request);

        $requisitions = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $requisitions]);
    }

    public function requisitionApprovalTurnaround(Request $request): JsonResponse
    {
        $data = PurchaseRequisition::select(
            'id',
            'requisition_number',
            'submitted_at',
            'approved_at',
            DB::raw('DATEDIFF(approved_at, submitted_at) as turnaround_days')
        )
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function sourcingQuotationComparison(Request $request): JsonResponse
    {
        $data = SupplierQuotation::with(['supplier', 'purchaseRequisition', 'items'])
            ->when($request->has('purchase_requisition_id'), fn ($q) => $q->where('purchase_requisition_id', $request->input('purchase_requisition_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function nonLowestPriceRecommendation(Request $request): JsonResponse
    {
        $data = QuotationRecommendation::with(['purchaseRequisition', 'selectedQuotation.supplier'])
            ->whereNotNull('non_lowest_price_reason')
            ->where('non_lowest_price_reason', '!=', '')
            ->when($request->has('business_entity_id'), fn ($q) => $q->whereHas('purchaseRequisition', fn ($sq) => $sq->where('business_entity_id', $request->input('business_entity_id'))))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function supplierQuotationAward(Request $request): JsonResponse
    {
        $data = PurchaseOrder::with(['supplier', 'quotationRecommendation', 'selectedQuotation', 'requisition'])
            ->whereNotIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_CANCELLED])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function purchaseOrderRegister(Request $request): JsonResponse
    {
        $data = PurchaseOrder::with(['supplier', 'requisition', 'businessEntity', 'financialYear'])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function purchaseOrderStatusReport(Request $request): JsonResponse
    {
        $status = $request->input('status');

        $query = PurchaseOrder::with(['supplier', 'requisition', 'businessEntity']);
        $this->applyEntityFilter($query, $request);

        if ($status) {
            $query->where('status', $status);
        }

        $data = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function grnInspectionReport(Request $request): JsonResponse
    {
        $data = GoodsReceiptNote::with(['purchaseOrder', 'supplier', 'businessEntity', 'inspectedBy'])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function supplierInvoiceVarianceReport(Request $request): JsonResponse
    {
        $data = SupplierInvoice::with(['supplier', 'purchaseOrder', 'matchRecords'])
            ->whereIn('status', [SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function paymentVoucherRegister(Request $request): JsonResponse
    {
        $data = PaymentVoucher::with(['supplier', 'supplierInvoice', 'businessEntity', 'preparedBy', 'approvedBy', 'paidBy'])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function outstandingSupplierLiabilities(Request $request): JsonResponse
    {
        $data = SupplierInvoice::with(['supplier', 'purchaseOrder', 'businessEntity'])
            ->whereIn('status', [SupplierInvoice::STATUS_SUBMITTED, SupplierInvoice::STATUS_MATCHED, SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT])
            ->where('outstanding_amount', '>', 0)
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderBy('due_date')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function budgetCommitmentReport(Request $request): JsonResponse
    {
        $data = BudgetTransaction::with(['businessEntity', 'financialYear', 'actor'])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->when($request->has('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->input('financial_year_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function procurementSpendReport(Request $request): JsonResponse
    {
        $groupBy = $request->input('group_by', 'entity');

        $query = PaymentVoucher::where('status', PaymentVoucher::STATUS_PAID);

        if ($groupBy === 'entity') {
            $data = $query->select('business_entity_id', DB::raw('sum(amount_paid) as total'))
                ->with('businessEntity')
                ->groupBy('business_entity_id')
                ->get();
        } elseif ($groupBy === 'department') {
            $data = $query->select('business_entity_id', DB::raw('sum(amount_paid) as total'))
                ->with('businessEntity')
                ->groupBy('business_entity_id')
                ->get();
        } elseif ($groupBy === 'supplier') {
            $data = $query->select('supplier_id', DB::raw('sum(amount_paid) as total'))
                ->with('supplier')
                ->groupBy('supplier_id')
                ->get();
        } elseif ($groupBy === 'financial_year') {
            $data = $query->select('financial_year_id', DB::raw('sum(amount_paid) as total'))
                ->with('financialYear')
                ->groupBy('financial_year_id')
                ->get();
        }

        return response()->json(['data' => $data]);
    }

    public function procurementCycleTimeReport(Request $request): JsonResponse
    {
        $data = PurchaseRequisition::select(
            'id',
            'requisition_number',
            'submitted_at',
            'approved_at',
            'purchase_order_id'
        )
            ->with(['purchaseOrder' => fn ($q) => $q->select('id', 'purchase_requisition_id', 'issued_at', 'expected_delivery_date')])
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function supplierPerformanceReport(Request $request): JsonResponse
    {
        $supplierId = $request->input('supplier_id');

        $query = PurchaseOrder::select(
            'supplier_id',
            DB::raw('count(*) as total_pos'),
            DB::raw('sum(total_amount) as total_value'),
            DB::raw('sum(case when status = "cancelled" then 1 else 0 end) as cancelled_pos'),
            DB::raw('sum(case when status = "fully_received" or status = "closed" then 1 else 0 end) as completed_pos')
        )
            ->with('supplier')
            ->whereNotIn('status', [PurchaseOrder::STATUS_DRAFT])
            ->groupBy('supplier_id');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $data = $query->get()->map(function ($item) {
            $grns = GoodsReceiptNote::whereHas('purchaseOrder', fn ($q) => $q->where('supplier_id', $item->supplier_id))
                ->select(DB::raw('count(*) as total'), DB::raw('sum(case when status = "rejected" then 1 else 0 end) as rejected'))
                ->first();

            $invoices = SupplierInvoice::whereHas('purchaseOrder', fn ($q) => $q->where('supplier_id', $item->supplier_id))
                ->select(DB::raw('count(*) as total'), DB::raw('sum(case when status = "matched_with_variance" then 1 else 0 end) as with_variance'))
                ->first();

            return [
                'supplier' => $item->supplier,
                'total_pos' => $item->total_pos,
                'total_value' => $item->total_value,
                'completed_pos' => $item->completed_pos,
                'cancelled_pos' => $item->cancelled_pos,
                'total_grns' => $grns->total ?? 0,
                'rejected_grns' => $grns->rejected ?? 0,
                'total_invoices' => $invoices->total ?? 0,
                'invoices_with_variance' => $invoices->with_variance ?? 0,
                'completion_rate' => $item->total_pos > 0 ? round(($item->completed_pos / $item->total_pos) * 100, 2) : 0,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function closureExceptionReport(Request $request): JsonResponse
    {
        $data = ProcurementClosure::with(['purchaseRequisition', 'purchaseOrder', 'closedBy'])
            ->where('closure_status', 'closed_with_exception')
            ->when($request->has('business_entity_id'), fn ($q) => $q->whereHas('purchaseRequisition', fn ($sq) => $sq->where('business_entity_id', $request->input('business_entity_id'))))
            ->orderByDesc('closed_at')
            ->paginate($request->input('per_page', 50));

        return response()->json(['data' => $data]);
    }

    public function auditTimeline(Request $request): JsonResponse
    {
        $requisitionId = $request->input('purchase_requisition_id');
        $poId = $request->input('purchase_order_id');
        $userId = $request->input('user_id');

        $timeline = collect();

        if ($requisitionId) {
            $requisition = PurchaseRequisition::with(['approvals.actor', 'quotations', 'quotationRecommendations'])->find($requisitionId);
            if ($requisition) {
                $timeline->push(['type' => 'requisition', 'model' => $requisition]);
                foreach ($requisition->approvals as $approval) {
                    $timeline->push(['type' => 'requisition_approval', 'model' => $approval]);
                }
            }
        }

        if ($poId) {
            $po = PurchaseOrder::with(['approvals.actor', 'goodsReceiptNotes', 'goodsReceiptNotes.approvals.actor', 'supplierInvoices', 'supplierInvoices.matchRecords', 'supplierInvoices.paymentVouchers', 'supplierInvoices.paymentVouchers.approvals.actor'])->find($poId);
            if ($po) {
                $timeline->push(['type' => 'purchase_order', 'model' => $po]);
                foreach ($po->approvals as $approval) {
                    $timeline->push(['type' => 'po_approval', 'model' => $approval]);
                }
                foreach ($po->goodsReceiptNotes as $grn) {
                    $timeline->push(['type' => 'grn', 'model' => $grn]);
                    foreach ($grn->approvals as $approval) {
                        $timeline->push(['type' => 'grn_approval', 'model' => $approval]);
                    }
                }
                foreach ($po->supplierInvoices as $invoice) {
                    $timeline->push(['type' => 'invoice', 'model' => $invoice]);
                    foreach ($invoice->matchRecords as $match) {
                        $timeline->push(['type' => 'match_record', 'model' => $match]);
                    }
                    foreach ($invoice->paymentVouchers as $voucher) {
                        $timeline->push(['type' => 'payment_voucher', 'model' => $voucher]);
                        foreach ($voucher->approvals as $approval) {
                            $timeline->push(['type' => 'payment_approval', 'model' => $approval]);
                        }
                    }
                }
            }
        }

        if ($userId) {
            $userApprovals = RequisitionApproval::where('actor_id', $userId)
                ->with('purchaseRequisition')
                ->get();
            foreach ($userApprovals as $approval) {
                $timeline->push(['type' => 'user_requisition_approval', 'model' => $approval]);
            }
        }

        $sorted = $timeline->sortBy(function ($item) {
            $date = $item['model']->created_at ?? $item['model']->action_at ?? null;

            return $date ? $date->timestamp : 0;
        })->values();

        return response()->json(['data' => $sorted]);
    }
}
