<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetTransactionRequest;
use App\Http\Requests\StoreEntityBudgetRequest;
use App\Http\Requests\SubmitEntityBudgetRequest;
use App\Http\Requests\UpdateEntityBudgetRequest;
use App\Http\Resources\BudgetHistoryResource;
use App\Models\ActivityLog;
use App\Models\BudgetApproval;
use App\Models\EntityBudget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntityBudgetController extends Controller
{
    private BudgetService $service;

    public function __construct(BudgetService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EntityBudget::class);

        $query = EntityBudget::with(['businessEntity', 'financialYear', 'proposedBy', 'approvedBy'])
            ->withCount(['approvals', 'transactions']);

        if ($request->filled('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->input('financial_year_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('active_year_only')) {
            $query->whereHas('financialYear', fn ($query) => $query->where('is_active', true));
        }

        $budgets = $query->paginate($request->input('per_page', 15));

        return response()->json($budgets);
    }

    public function store(StoreEntityBudgetRequest $request): JsonResponse
    {
        $this->authorize('create', EntityBudget::class);

        $data = $request->validated();
        $data['proposed_by'] = Auth::id();
        $data['status'] = EntityBudget::STATUS_DRAFT;
        $data['committed_amount'] = 0;
        $data['spent_amount'] = 0;
        $data['available_amount'] = 0;

        $budget = null;
        DB::transaction(function () use ($data, &$budget) {
            $budget = EntityBudget::create($data);
            ActivityLog::record(Auth::user(), 'entity_budget.created', $budget, [], $budget->toArray());
        });

        return response()->json(['message' => 'Budget draft created successfully.', 'data' => $budget], 201);
    }

    public function show(EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('view', $entityBudget);

        $entityBudget->load(['businessEntity', 'financialYear', 'proposedBy', 'approvedBy', 'approvals.actor', 'transactions.createdBy']);
        $entityBudget->available_amount = $entityBudget->calculateAvailable();

        return response()->json($entityBudget);
    }

    public function update(UpdateEntityBudgetRequest $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('update', $entityBudget);

        if (! in_array($entityBudget->status, [EntityBudget::STATUS_DRAFT, EntityBudget::STATUS_RETURNED], true)) {
            return response()->json(['message' => 'Only draft or returned budgets can be updated.'], 422);
        }

        $data = $request->validated();
        $oldValues = $entityBudget->only(['business_entity_id', 'financial_year_id', 'proposed_amount', 'notes']);

        DB::transaction(function () use ($entityBudget, $data, $oldValues) {
            $entityBudget->update($data);
            ActivityLog::record(Auth::user(), 'entity_budget.updated', $entityBudget, $oldValues, $entityBudget->only(['business_entity_id', 'financial_year_id', 'proposed_amount', 'notes']));
        });

        return response()->json(['message' => 'Budget updated successfully.', 'data' => $entityBudget]);
    }

    public function submit(SubmitEntityBudgetRequest $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('submit', $entityBudget);

        if (! in_array($entityBudget->status, [EntityBudget::STATUS_DRAFT, EntityBudget::STATUS_RETURNED], true)) {
            return response()->json(['message' => 'Only draft or returned budgets can be submitted.'], 422);
        }

        DB::transaction(function () use ($entityBudget) {
            $oldValues = ['status' => $entityBudget->status];
            $entityBudget->update([
                'status' => EntityBudget::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
            BudgetApproval::create([
                'entity_budget_id' => $entityBudget->id,
                'action' => BudgetApproval::ACTION_SUBMITTED,
                'actor_id' => Auth::id(),
                'comments' => null,
                'action_at' => now(),
            ]);
            ActivityLog::record(Auth::user(), 'entity_budget.submitted', $entityBudget, $oldValues, ['status' => EntityBudget::STATUS_SUBMITTED]);
        });

        return response()->json(['message' => 'Budget submitted for approval.', 'data' => $entityBudget]);
    }

    public function storeTransaction(StoreBudgetTransactionRequest $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('postTransaction', $entityBudget);

        $transaction = $this->service->postTransaction($entityBudget, $request->validated(), Auth::user());

        return response()->json(['message' => 'Budget transaction recorded successfully.', 'data' => $transaction], 201);
    }

    public function history(EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('view', $entityBudget);

        $entityBudget->load(['transactions.createdBy', 'approvals.actor', 'activityLogs.actor', 'businessEntity', 'financialYear', 'proposedBy', 'approvedBy']);

        return response()->json(new BudgetHistoryResource($entityBudget));
    }

    public function destroy(EntityBudget $entityBudget): JsonResponse
    {
        return response()->json(['message' => 'Budgets cannot be deleted. Change status to returned, rejected, or closed instead.'], 405);
    }
}
