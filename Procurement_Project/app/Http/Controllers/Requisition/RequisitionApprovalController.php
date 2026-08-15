<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionDecisionRequest;
use App\Http\Resources\PurchaseRequisitionResource;
use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\RequisitionApproval;
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

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Only submitted requisitions can be approved.'], 422);
        }

        $budget = $this->budgetService->verifyBudgetForSubmission($purchaseRequisition);

        DB::transaction(function () use ($purchaseRequisition, $request, $budget) {
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                'approved_at' => now(),
            ]);

            $transaction = $this->budgetService->commitRequisition($purchaseRequisition, $budget, Auth::user());

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'approved',
                'actor_id' => Auth::id(),
                'comments' => $request->input('comments'),
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.approved', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_SUBMITTED], ['status' => PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, 'commitment_transaction_id' => $transaction->id]);
        });

        return response()->json(['message' => 'Purchase requisition approved for sourcing.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function return(RequisitionDecisionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('return', $purchaseRequisition);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Only submitted requisitions can be returned.'], 422);
        }

        DB::transaction(function () use ($purchaseRequisition, $request) {
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

            ActivityLog::record(Auth::user(), 'purchase_requisition.returned', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_SUBMITTED], ['status' => PurchaseRequisition::STATUS_RETURNED]);
        });

        return response()->json(['message' => 'Purchase requisition returned to requester.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function reject(RequisitionDecisionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('reject', $purchaseRequisition);

        if ($purchaseRequisition->status !== PurchaseRequisition::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Only submitted requisitions can be rejected.'], 422);
        }

        DB::transaction(function () use ($purchaseRequisition, $request) {
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

            ActivityLog::record(Auth::user(), 'purchase_requisition.rejected', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_SUBMITTED], ['status' => PurchaseRequisition::STATUS_REJECTED]);
        });

        return response()->json(['message' => 'Purchase requisition rejected.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }
}
