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
use App\Services\EntityAccessService;
use App\Services\RequisitionBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequisitionController extends Controller
{
    private PurchaseRequisitionService $service;

    private RequisitionBudgetService $budgetService;

    private EntityAccessService $entityAccess;

    public function __construct(PurchaseRequisitionService $service, RequisitionBudgetService $budgetService, EntityAccessService $entityAccess)
    {
        $this->middleware('auth');
        $this->service = $service;
        $this->budgetService = $budgetService;
        $this->entityAccess = $entityAccess;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        $query = PurchaseRequisition::with(['businessEntity', 'department', 'supplierCategory', 'requester', 'lineManager', 'items', 'attachments', 'approvals.actor']);
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])) {
            // full access
        } elseif ($user->hasRole('procurement_officer')) {
            $query->whereIn('status', [
                PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                PurchaseRequisition::STATUS_QUOTATIONS_READY,
                PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
            ]);
        } elseif ($user->hasAnyRole(['line_manager', 'department_head'])) {
            $query->where(function ($query) use ($user) {
                $query->where('line_manager_id', $user->id)
                    ->orWhere('requester_id', $user->id);
            });
        } else {
            $query->where('requester_id', $user->id);
        }

        $this->entityAccess->apply($query, $request, $user);

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

        $this->budgetService->recordCheck($requisition, $this->budgetService->checkAvailability($requisition));

        ActivityLog::record(Auth::user(), 'purchase_requisition.created', $requisition, [], $requisition->toArray());

        return response()->json(['message' => 'Purchase requisition draft created successfully.', 'data' => new PurchaseRequisitionResource($requisition)], 201);
    }

    public function show(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('view', $purchaseRequisition);

        $purchaseRequisition->load(['businessEntity', 'department', 'supplierCategory', 'requester', 'lineManager', 'items', 'attachments', 'approvals.actor', 'activityLogs.actor']);

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

        $purchaseRequisition->refresh();
        $this->budgetService->recordCheck($purchaseRequisition, $this->budgetService->checkAvailability($purchaseRequisition));

        ActivityLog::record(Auth::user(), 'purchase_requisition.updated', $purchaseRequisition, [], $purchaseRequisition->toArray());

        return response()->json(['message' => 'Purchase requisition updated successfully.', 'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh())]);
    }

    public function submit(SubmitPurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('submit', $purchaseRequisition);

        $lineManager = $this->service->resolveLineManager(Auth::user());
        $check = $this->budgetService->checkAvailability($purchaseRequisition);
        $data = $request->validated();

        if ($check['requires_acknowledgement'] && ! ($data['budget_shortfall_acknowledged'] ?? false)) {
            throw ValidationException::withMessages([
                'budget_shortfall_acknowledged' => 'Acknowledge the budget shortfall and provide the intended funding or loan justification before submitting.',
            ]);
        }

        $isLineManagerRequest = Auth::user()->hasAnyRole(['line_manager', 'department_head'])
            && Auth::id() === $purchaseRequisition->requester_id;
        $nextStatus = $isLineManagerRequest
            ? PurchaseRequisition::STATUS_PENDING_GM_APPROVAL
            : PurchaseRequisition::STATUS_SUBMITTED;

        DB::transaction(function () use ($purchaseRequisition, $lineManager, $check, $data, $isLineManagerRequest, $nextStatus) {
            $this->budgetService->recordCheck(
                $purchaseRequisition,
                $check,
                $check['requires_acknowledgement'] ? Auth::user() : null,
                $data['budget_shortfall_reason'] ?? null,
            );
            $purchaseRequisition->update([
                'line_manager_id' => $lineManager->id,
                'status' => $nextStatus,
                'submitted_at' => now(),
            ]);

            RequisitionApproval::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'action' => 'submitted',
                'actor_id' => Auth::id(),
                'comments' => null,
                'action_at' => now(),
            ]);

            if ($isLineManagerRequest) {
                RequisitionApproval::create([
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'action' => 'line_manager_approved',
                    'actor_id' => Auth::id(),
                    'comments' => 'Line manager approval recorded on submission of their own requisition.',
                    'action_at' => now(),
                ]);
            }

            ActivityLog::record(Auth::user(), $isLineManagerRequest ? 'purchase_requisition.line_manager_submitted' : 'purchase_requisition.submitted', $purchaseRequisition, ['status' => PurchaseRequisition::STATUS_DRAFT], [
                'status' => $nextStatus,
                'budget_check_status' => $check['status'],
                'budget_shortfall_amount' => $check['shortfall_amount'],
                'shortfall_acknowledged' => $check['requires_acknowledgement'],
            ]);
        });

        return response()->json([
            'message' => $isLineManagerRequest
                ? 'Line manager requisition approved and submitted directly to the GM.'
                : 'Purchase requisition submitted to the assigned line manager.',
            'data' => new PurchaseRequisitionResource($purchaseRequisition->fresh()),
        ]);
    }

    public function budgetCheck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_entity_id' => ['required', 'integer', 'exists:business_entities,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])
                || (int) $user->department?->business_entity_id === (int) $data['business_entity_id'],
            403,
        );

        $probe = new PurchaseRequisition([
            'business_entity_id' => $data['business_entity_id'],
            'estimated_amount' => $data['amount'],
        ]);

        $check = $this->budgetService->checkAvailability($probe);

        return response()->json(['data' => $this->budgetService->visibleCheck($check, $user)]);
    }

    public function cancel(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('cancel', $purchaseRequisition);

        if (! in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_DRAFT, PurchaseRequisition::STATUS_RETURNED, PurchaseRequisition::STATUS_SUBMITTED, PurchaseRequisition::STATUS_PENDING_GM_APPROVAL], true)) {
            return response()->json(['message' => 'Only draft or approval-pending requisitions can be cancelled.'], 422);
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
