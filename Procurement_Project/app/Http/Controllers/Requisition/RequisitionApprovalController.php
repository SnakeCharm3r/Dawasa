<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionDecisionRequest;
use App\Http\Resources\PurchaseRequisitionResource;
use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\RequisitionApproval;
use App\Models\User;
use App\Services\RequisitionBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionApprovalController extends Controller
{
    private RequisitionBudgetService $budgetService;

    public function __construct(RequisitionBudgetService $budgetService)
    {
        $this->middleware('auth');
        $this->budgetService = $budgetService;
    }

    public function approve(RequisitionDecisionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('approve', $purchaseRequisition);

        if ($purchaseRequisition->status === PurchaseRequisition::STATUS_SUBMITTED) {
            DB::transaction(function () use ($purchaseRequisition, $request) {
                $purchaseRequisition->update(['status' => PurchaseRequisition::STATUS_PENDING_GM_APPROVAL]);

                RequisitionApproval::create([
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'action' => 'line_manager_approved',
                    'actor_id' => Auth::id(),
                    'comments' => $request->input('comments'),
                    'action_at' => now(),
                ]);

                ActivityLog::record(Auth::user(), 'purchase_requisition.line_manager_approved', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_SUBMITTED], ['status' => PurchaseRequisition::STATUS_PENDING_GM_APPROVAL]);
            });

            return response()->json(['message' => 'Line manager approved the requisition. It is now awaiting GM approval.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
        }

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_PENDING_GM_APPROVAL) {
            return response()->json(['message' => 'Only requisitions awaiting line-manager or GM approval can be approved.'], 422);
        }

        $check = $this->budgetService->checkAvailability($purchaseRequisition);
        $budget = $this->budgetService->approvedBudget($purchaseRequisition);

        DB::transaction(function () use ($purchaseRequisition, $request, $check, $budget) {
            $acknowledger = null;
            if ($check['requires_acknowledgement']) {
                $acknowledger = $purchaseRequisition->budget_shortfall_acknowledged_by
                    ? User::find($purchaseRequisition->budget_shortfall_acknowledged_by)
                    : Auth::user();
            }
            $this->budgetService->recordCheck(
                $purchaseRequisition,
                $check,
                $acknowledger,
                $purchaseRequisition->budget_shortfall_reason ?: $request->input('comments'),
            );
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                'approved_at' => now(),
                'committed_amount' => $purchaseRequisition->estimated_amount,
            ]);

            $transaction = $budget
                ? $this->budgetService->commitRequisition($purchaseRequisition, $budget, Auth::user())
                : null;

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'gm_approved',
                'actor_id' => Auth::id(),
                'comments' => $request->input('comments'),
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.gm_approved', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_PENDING_GM_APPROVAL], [
                'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                'budget_check_status' => $check['status'],
                'budget_shortfall_amount' => $check['shortfall_amount'],
                'commitment_transaction_id' => $transaction?->id,
            ]);
        });

        return response()->json(['message' => 'GM approved the requisition for sourcing.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function return(RequisitionDecisionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('return', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_SUBMITTED, PurchaseRequisition::STATUS_PENDING_GM_APPROVAL], true)) {
            return response()->json(['message' => 'Only approval-pending requisitions can be returned.'], 422);
        }

        $oldStatus = $purchaseRequisition->status;
        DB::transaction(function () use ($purchaseRequisition, $request, $oldStatus) {
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_RETURNED,
                'returned_at' => now(),
            ]);

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'returned',
                'actor_id' => Auth::id(),
                'comments' => $request->input('comments'),
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.returned', $purchaseRequisition, ['status' => $oldStatus], ['status' => PurchaseRequisition::STATUS_RETURNED]);
        });

        return response()->json(['message' => 'Purchase requisition returned to requester.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function reject(RequisitionDecisionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('reject', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_SUBMITTED, PurchaseRequisition::STATUS_PENDING_GM_APPROVAL], true)) {
            return response()->json(['message' => 'Only approval-pending requisitions can be rejected.'], 422);
        }

        $oldStatus = $purchaseRequisition->status;
        DB::transaction(function () use ($purchaseRequisition, $request, $oldStatus) {
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_REJECTED,
                'rejected_at' => now(),
            ]);

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'rejected',
                'actor_id' => Auth::id(),
                'comments' => $request->input('comments'),
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.rejected', $purchaseRequisition, ['status' => $oldStatus], ['status' => PurchaseRequisition::STATUS_REJECTED]);
        });

        return response()->json(['message' => 'Purchase requisition rejected.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }
}
