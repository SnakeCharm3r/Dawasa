<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveEntityBudgetRequest;
use App\Models\BudgetApproval;
use App\Models\EntityBudget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetApprovalController extends Controller
{
    private BudgetService $service;

    public function __construct(BudgetService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    public function approve(ApproveEntityBudgetRequest $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('approve', $entityBudget);

        $data = $request->validated();

        $budget = $this->service->approveBudget($entityBudget, (float) $data['approved_amount'], $data['comments'] ?? null, Auth::user());

        return response()->json(['message' => 'Budget approved successfully.', 'data' => $budget]);
    }

    public function returnBudget(Request $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('return', $entityBudget);

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:2000'],
        ]);

        $budget = $this->service->returnBudget($entityBudget, $validated['comments'], Auth::user());

        return response()->json(['message' => 'Budget returned for revision.', 'data' => $budget]);
    }

    public function reject(Request $request, EntityBudget $entityBudget): JsonResponse
    {
        $this->authorize('reject', $entityBudget);

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:2000'],
        ]);

        $budget = $this->service->rejectBudget($entityBudget, $validated['comments'], Auth::user());

        return response()->json(['message' => 'Budget rejected.', 'data' => $budget]);
    }
}
