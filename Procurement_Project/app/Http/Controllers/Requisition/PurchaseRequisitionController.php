<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkQuotationsReadyRequest;
use App\Http\Requests\StorePurchaseRequisitionRequest;
use App\Http\Requests\SubmitPurchaseRequisitionRequest;
use App\Http\Requests\UpdatePurchaseRequisitionRequest;
use App\Http\Resources\PurchaseRequisitionResource;
use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\RequisitionApproval;
use App\Services\PurchaseRequisitionService;
use App\Services\RequisitionBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    private PurchaseRequisitionService $service;

    private RequisitionBudgetService $budgetService;

    public function __construct(PurchaseRequisitionService $service, RequisitionBudgetService $budgetService)
    {
        $this->middleware('auth');
        $this->service = $service;
        $this->budgetService = $budgetService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        $query = PurchaseRequisition::with(['businessEntity', 'department', 'requester', 'lineManager', 'items', 'attachments', 'approvals.actor']);
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            // full access
        } elseif ($user->hasRole('procurement_officer')) {
            $query->whereIn('status', [
                PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                PurchaseRequisition::STATUS_QUOTATIONS_READY,
                PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
            ]);
        } elseif ($user->hasRole('department_head')) {
            $query->where('department_id', $user->department_id);
        } elseif ($user->hasRole('line_manager')) {
            $query->where('department_id', $user->department_id);
        } else {
            $query->where('requester_id', $user->id);
        }

        if ($request->filled('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('requester_id')) {
            $query->where('requester_id', $request->input('requester_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json(PurchaseRequisitionResource::collection($paginator));
    }

    public function store(StorePurchaseRequisitionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $requisition = DB::transaction(function () use ($data) {
            return $this->service->createDraft(Auth::user(), $data);
        });

        ActivityLog::record(Auth::user(), 'purchase_requisition.created', $requisition, [], $requisition->toArray());

        return response()->json(['message' => 'Purchase requisition draft created successfully.', 'data' => new PurchaseRequisitionResource($requisition)], 201);
    }

    public function show(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('view', $purchaseRequisition);

        $purchaseRequisition->load(['businessEntity', 'department', 'requester', 'lineManager', 'items', 'attachments', 'approvals.actor', 'activityLogs.actor']);

        return response()->json(new PurchaseRequisitionResource($purchaseRequisition));
    }

    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('update', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED], true)) {
            return response()->json(['message' => 'Only draft or returned requisitions can be updated.'], 422);
        }

        $data = $request->validated();

        DB::transaction(function () use ($purchaseRequisition, $data) {
            $this->service->updateDraft($purchaseRequisition, $data);
        });

        ActivityLog::record(Auth::user(), 'purchase_requisition.updated', $purchaseRequisition, [], $purchaseRequisition->toArray());

        return response()->json(['message' => 'Purchase requisition updated successfully.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function submit(SubmitPurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('submit', $purchaseRequisition);

        $budget = $this->budgetService->verifyBudgetForSubmission($purchaseRequisition);

        DB::transaction(function () use ($purchaseRequisition) {
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'submitted',
                'actor_id' => Auth::id(),
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.submitted', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_DRAFT], ['status' => PurchaseRequisition::STATUS_SUBMITTED]);
        });

        return response()->json(['message' => 'Purchase requisition submitted successfully.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function cancel(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('cancel', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED, PurchaseRequisition::STATUS_SUBMITTED], true)) {
            return response()->json(['message' => 'Only draft, returned, or submitted requisitions can be cancelled.'], 422);
        }

        DB::transaction(function () use ($purchaseRequisition) {
            $purchaseRequisition->update([
                'status' => PurchaseRequisition::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'cancelled',
                'actor_id' => Auth::id(),
                'comments' => null,
                'action_at' => now(),
            ]);

            ActivityLog::record(Auth::user(), 'purchase_requisition.cancelled', $purchaseRequisition, ['status' => $purchaseRequisition->status], ['status' => PurchaseRequisition::STATUS_CANCELLED]);
        });

        return response()->json(['message' => 'Purchase requisition cancelled successfully.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function markQuotationsReady(MarkQuotationsReadyRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('markQuotationsReady', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, PurchaseRequisition::STATUS_RETURNED_TO_SOURCING], true)) {
            return response()->json(['message' => 'Only sourcing-approved or returned-to-sourcing requisitions can be marked ready for recommendations.'], 422);
        }

        $oldValues = ['status' => $purchaseRequisition->status];

        $purchaseRequisition = $this->service->markQuotationsReady($purchaseRequisition);

        ActivityLog::record(Auth::user(), 'purchase_requisition.quotations_ready', $purchaseRequisition, $oldValues, ['status' => PurchaseRequisition::STATUS_QUOTATIONS_READY]);

        return response()->json(['message' => 'Requisition marked ready for quotation recommendations.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function destroy(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        return response()->json(['message' => 'Purchase requisitions cannot be deleted.'], 405);
    }
}
