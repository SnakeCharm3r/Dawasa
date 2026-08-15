<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Models\BudgetTransaction;
use App\Models\EntityBudget;
use App\Models\GoodsReceiptNote;
use App\Models\PaymentVoucher;
use App\Models\ProcurementClosure;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Models\SupplierInvoice;
use App\Models\SupplierQuotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementDashboardController extends Controller
{
    protected function applyEntityFilter($query, Request $request)
    {
        if ($request->has('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }
        if ($request->has('financial_year_id')) {
            $query->where('financial_year_id', $request->input('financial_year_id'));
        }

        return $query;
    }

    public function executive(Request $request): JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['super_admin', 'gm', 'auditor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $canSeeBudgetData = auth()->user()->hasAnyRole(['super_admin', 'gm', 'accountant', 'auditor']);

        $requisitionsByStatus = PurchaseRequisition::select('status', DB::raw('count(*) as count'))
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->groupBy('status')
            ->get();

        $requisitionValueByEntity = PurchaseRequisition::select('business_entity_id', DB::raw('sum(committed_amount) as total'))
            ->whereNotNull('committed_amount')
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->with('businessEntity')
            ->groupBy('business_entity_id')
            ->get();

        $budgetSummaries = [];
        if ($canSeeBudgetData) {
            $budgetSummaries = EntityBudget::select(
                'business_entity_id',
                'financial_year_id',
                'proposed_amount',
                'approved_amount',
                'committed_amount',
                'spent_amount',
                'available_amount',
            )
                ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
                ->when($request->has('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->input('financial_year_id')))
                ->with(['businessEntity', 'financialYear'])
                ->get();
        }

        $poValueBySupplier = PurchaseOrder::select('supplier_id', DB::raw('sum(total_amount) as total'))
            ->whereNotIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_CANCELLED])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->with('supplier')
            ->groupBy('supplier_id')
            ->get();

        $unpaidInvoices = SupplierInvoice::select(DB::raw('sum(outstanding_amount) as total'), DB::raw('count(*) as count'))
            ->whereIn('status', [SupplierInvoice::STATUS_SUBMITTED, SupplierInvoice::STATUS_MATCHED, SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT])
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->first();

        $overdueInvoices = SupplierInvoice::select(DB::raw('sum(outstanding_amount) as total'), DB::raw('count(*) as count'))
            ->whereIn('status', [SupplierInvoice::STATUS_SUBMITTED, SupplierInvoice::STATUS_MATCHED, SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT])
            ->where('due_date', '<', now())
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->first();

        $paymentTotals = PaymentVoucher::select('business_entity_id', 'financial_year_id', DB::raw('sum(amount_paid) as total'))
            ->where('status', PaymentVoucher::STATUS_PAID)
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->when($request->has('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->input('financial_year_id')))
            ->with(['businessEntity', 'financialYear'])
            ->groupBy('business_entity_id', 'financial_year_id')
            ->get();

        $exceptions = [
            'non_lowest_quote_selections' => QuotationRecommendation::whereNotNull('non_lowest_price_reason')
                ->where('non_lowest_price_reason', '!=', '')
                ->count(),
            'rejected_grns' => GoodsReceiptNote::where('status', GoodsReceiptNote::STATUS_REJECTED)->count(),
            'overdue_pos' => PurchaseOrder::where('expected_delivery_date', '<', now())
                ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_CLOSED, PurchaseOrder::STATUS_FULLY_RECEIVED])
                ->count(),
        ];

        $quotationSavings = null;
        if ($canSeeBudgetData) {
            $quotationSavings = PurchaseRequisition::select(DB::raw('sum(estimated_amount - committed_amount) as savings'))
                ->whereNotNull('committed_amount')
                ->where('estimated_amount', '>', 0)
                ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
                ->first();
        }

        return response()->json([
            'data' => [
                'requisitions_by_status' => $requisitionsByStatus,
                'requisition_value_by_entity' => $requisitionValueByEntity,
                'budget_summaries' => $budgetSummaries,
                'po_value_by_supplier' => $poValueBySupplier,
                'unpaid_invoices' => $unpaidInvoices,
                'overdue_invoices' => $overdueInvoices,
                'payment_totals' => $paymentTotals,
                'exceptions' => $exceptions,
                'quotation_savings' => $quotationSavings,
            ],
        ]);
    }

    public function operational(Request $request): JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['super_admin', 'procurement_officer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = PurchaseRequisition::query();

        $this->applyEntityFilter($query, $request);

        $waitingForSourcing = (clone $query)->where('status', PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING)->count();
        $quotationsReady = (clone $query)->where('status', PurchaseRequisition::STATUS_QUOTATIONS_READY)->count();
        $awaitingGMDecision = (clone $query)->where('status', PurchaseRequisition::STATUS_PENDING_FINAL_APPROVAL)->count();

        $poQuery = PurchaseOrder::query();
        $this->applyEntityFilter($poQuery, $request);

        $pendingAccountantConfirmation = (clone $poQuery)->where('status', PurchaseOrder::STATUS_PENDING_ACCOUNTANT_CONFIRMATION)->count();
        $awaitingIssue = (clone $poQuery)->where('status', PurchaseOrder::STATUS_CONFIRMED)->count();
        $awaitingDelivery = (clone $poQuery)->where('status', PurchaseOrder::STATUS_ISSUED)->count();
        $partiallyReceived = (clone $poQuery)->where('status', PurchaseOrder::STATUS_PARTIALLY_RECEIVED)->count();
        $overdueDeliveries = (clone $poQuery)->where('expected_delivery_date', '<', now())
            ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_CLOSED, PurchaseOrder::STATUS_FULLY_RECEIVED])
            ->count();

        $grnQuery = GoodsReceiptNote::query();
        $this->applyEntityFilter($grnQuery, $request);

        $awaitingInspection = (clone $grnQuery)->where('status', GoodsReceiptNote::STATUS_SUBMITTED)->count();

        $closureQuery = ProcurementClosure::query();
        $this->applyEntityFilter($closureQuery->whereHas('purchaseRequisition'), $request);

        $awaitingRequesterConfirmation = (clone $closureQuery)->where('closure_status', 'pending_requester_confirmation')->count();

        return response()->json([
            'data' => [
                'waiting_for_sourcing' => $waitingForSourcing,
                'quotations_ready' => $quotationsReady,
                'awaiting_gm_decision' => $awaitingGMDecision,
                'pending_accountant_confirmation' => $pendingAccountantConfirmation,
                'awaiting_issue' => $awaitingIssue,
                'awaiting_delivery' => $awaitingDelivery,
                'partially_received' => $partiallyReceived,
                'overdue_deliveries' => $overdueDeliveries,
                'awaiting_inspection' => $awaitingInspection,
                'awaiting_requester_confirmation' => $awaitingRequesterConfirmation,
            ],
        ]);
    }

    public function finance(Request $request): JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $budgetQuery = EntityBudget::query();
        $this->applyEntityFilter($budgetQuery, $request);

        $budgetSummaries = (clone $budgetQuery)->select(
            'business_entity_id',
            'financial_year_id',
            'proposed_amount',
            'approved_amount',
            'committed_amount',
            'spent_amount',
            'available_amount',
        )
            ->with(['businessEntity', 'financialYear'])
            ->get();

        $poCommitments = PurchaseOrder::select('business_entity_id', 'financial_year_id', DB::raw('sum(total_amount) as total'))
            ->whereNotIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_CANCELLED])
            ->whereDoesntHave('paymentVouchers', fn ($q) => $q->where('status', PaymentVoucher::STATUS_PAID))
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->when($request->has('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->input('financial_year_id')))
            ->with(['businessEntity', 'financialYear'])
            ->groupBy('business_entity_id', 'financial_year_id')
            ->get();

        $invoiceQuery = SupplierInvoice::query();
        $this->applyEntityFilter($invoiceQuery, $request);

        $awaitingMatching = (clone $invoiceQuery)->where('status', SupplierInvoice::STATUS_PENDING_MATCH)->count();
        $withVariance = (clone $invoiceQuery)->where('status', SupplierInvoice::STATUS_MATCHED_WITH_VARIANCE)->count();
        $approvedVouchers = PaymentVoucher::where('status', PaymentVoucher::STATUS_APPROVED)->count();
        $overdueInvoices = (clone $invoiceQuery)->where('due_date', '<', now())
            ->whereIn('status', [SupplierInvoice::STATUS_SUBMITTED, SupplierInvoice::STATUS_MATCHED, SupplierInvoice::STATUS_APPROVED_FOR_PAYMENT])
            ->count();

        $expenditureByEntity = BudgetTransaction::where('transaction_type', 'expenditure')
            ->select('business_entity_id', 'financial_year_id', DB::raw('sum(amount) as total'))
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->when($request->has('financial_year_id'), fn ($q) => $q->where('financial_year_id', $request->input('financial_year_id')))
            ->with(['businessEntity', 'financialYear'])
            ->groupBy('business_entity_id', 'financial_year_id')
            ->get();

        return response()->json([
            'data' => [
                'budget_summaries' => $budgetSummaries,
                'po_commitments' => $poCommitments,
                'awaiting_matching' => $awaitingMatching,
                'with_variance' => $withVariance,
                'approved_vouchers' => $approvedVouchers,
                'overdue_invoices' => $overdueInvoices,
                'expenditure_by_entity' => $expenditureByEntity,
            ],
        ]);
    }

    public function requester(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $myRequisitions = PurchaseRequisition::where('requester_id', $userId)
            ->with(['department', 'businessEntity', 'purchaseOrder', 'purchaseOrder.supplier'])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $awaitingMyAction = PurchaseRequisition::where('requester_id', $userId)
            ->where('status', PurchaseRequisition::STATUS_RETURNED)
            ->count();

        $awaitingMyConfirmation = ProcurementClosure::whereHas('purchaseRequisition', fn ($q) => $q->where('requester_id', $userId))
            ->where('closure_status', 'pending_requester_confirmation')
            ->count();

        return response()->json([
            'data' => [
                'my_requisitions' => $myRequisitions,
                'awaiting_my_action' => $awaitingMyAction,
                'awaiting_my_confirmation' => $awaitingMyConfirmation,
            ],
        ]);
    }

    public function auditor(Request $request): JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['super_admin', 'auditor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workflowCounts = [
            'requisitions' => PurchaseRequisition::count(),
            'quotations' => SupplierQuotation::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'grns' => GoodsReceiptNote::count(),
            'invoices' => SupplierInvoice::count(),
            'payments' => PaymentVoucher::count(),
            'closures' => ProcurementClosure::count(),
        ];

        $exceptions = [
            'returned_cases' => PurchaseRequisition::where('status', PurchaseRequisition::STATUS_RETURNED)->count(),
            'rejected_cases' => PurchaseRequisition::where('status', PurchaseRequisition::STATUS_REJECTED)->count(),
            'cancelled_cases' => PurchaseRequisition::where('status', PurchaseRequisition::STATUS_CANCELLED)->count(),
            'non_lowest_quotes' => QuotationRecommendation::whereNotNull('non_lowest_price_reason')
                ->where('non_lowest_price_reason', '!=', '')
                ->count(),
            'rejected_grns' => GoodsReceiptNote::where('status', GoodsReceiptNote::STATUS_REJECTED)->count(),
        ];

        $budgetAdjustments = BudgetTransaction::where('transaction_type', 'adjustment')
            ->when($request->has('business_entity_id'), fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')))
            ->count();

        return response()->json([
            'data' => [
                'workflow_counts' => $workflowCounts,
                'exceptions' => $exceptions,
                'budget_adjustments' => $budgetAdjustments,
            ],
        ]);
    }
}
