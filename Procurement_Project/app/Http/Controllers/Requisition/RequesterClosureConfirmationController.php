<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequesterClosureDecisionRequest;
use App\Models\ProcurementClosure;
use App\Services\ProcurementClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RequesterClosureConfirmationController extends Controller
{
    public function __construct(protected ProcurementClosureService $service)
    {
    }

    protected function runProtected(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    protected function loadRelations(ProcurementClosure $closure): void
    {
        $closure->load([
            'purchaseRequisition.requester',
            'purchaseRequisition.department',
            'purchaseRequisition.businessEntity',
            'purchaseOrder.supplier',
            'purchaseOrder.businessEntity',
            'requesterConfirmedBy',
            'closedBy',
            'approvals.actor',
        ]);
    }

    public function decision(RequesterClosureDecisionRequest $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $decision = $request->input('decision');

        if ($decision === 'confirm') {
            $this->authorize('confirm', $procurementClosure);
            return $this->runProtected(function () use ($procurementClosure, $request) {
                $closure = $this->service->requesterConfirm($procurementClosure, Auth::user(), $request->input('comments'));
                $this->loadRelations($closure);

                return response()->json([
                    'message' => 'Closure confirmed by requester.',
                    'data' => $closure,
                ]);
            });
        } else {
            $this->authorize('return', $procurementClosure);
            return $this->runProtected(function () use ($procurementClosure, $request) {
                $closure = $this->service->returnForResolution($procurementClosure, Auth::user(), $request->input('reason'));
                $this->loadRelations($closure);

                return response()->json([
                    'message' => 'Closure returned for resolution.',
                    'data' => $closure,
                ]);
            });
        }
    }
}
