<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelProcurementClosureRequest;
use App\Http\Requests\CloseWithExceptionRequest;
use App\Http\Requests\StoreProcurementClosureRequest;
use App\Http\Requests\SubmitProcurementClosureRequest;
use App\Http\Requests\UpdateProcurementClosureRequest;
use App\Models\ProcurementClosure;
use App\Models\PurchaseRequisition;
use App\Services\ProcurementClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProcurementClosureController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProcurementClosure::class);

        $query = ProcurementClosure::with(['purchaseRequisition', 'purchaseOrder', 'requesterConfirmedBy', 'closedBy']);

        if ($request->has('business_entity_id')) {
            $query->whereHas('purchaseRequisition', fn ($q) => $q->where('business_entity_id', $request->input('business_entity_id')));
        }

        if ($request->has('department_id')) {
            $query->whereHas('purchaseRequisition', fn ($q) => $q->where('department_id', $request->input('department_id')));
        }

        if ($request->has('requester_id')) {
            $query->whereHas('purchaseRequisition', fn ($q) => $q->where('requester_id', $request->input('requester_id')));
        }

        if ($request->has('supplier_id')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('supplier_id', $request->input('supplier_id')));
        }

        if ($request->has('po_number')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('purchase_order_number', 'like', '%'.$request->input('po_number').'%'));
        }

        if ($request->has('requisition_number')) {
            $query->whereHas('purchaseRequisition', fn ($q) => $q->where('requisition_number', 'like', '%'.$request->input('requisition_number').'%'));
        }

        if ($request->has('closure_status')) {
            $query->where('closure_status', $request->input('closure_status'));
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $closures = $query->orderByDesc('created_at')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $closures]);
    }

    public function show(ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('view', $procurementClosure);

        $this->loadRelations($procurementClosure);

        return response()->json(['data' => $procurementClosure]);
    }

    public function store(StoreProcurementClosureRequest $request): JsonResponse
    {
        $this->authorize('create', ProcurementClosure::class);

        return $this->runProtected(function () use ($request) {
            $requisition = PurchaseRequisition::findOrFail($request->input('purchase_requisition_id'));
            $closure = $this->service->createDraft($requisition, Auth::user(), $request->validated());
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Closure draft created successfully.',
                'data' => $closure,
            ], 201);
        });
    }

    public function update(UpdateProcurementClosureRequest $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('update', $procurementClosure);

        return $this->runProtected(function () use ($procurementClosure, $request) {
            $closure = $this->service->updateDraft($procurementClosure, Auth::user(), $request->validated());
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Closure draft updated successfully.',
                'data' => $closure,
            ]);
        });
    }

    public function submitForRequesterConfirmation(SubmitProcurementClosureRequest $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('submit', $procurementClosure);

        return $this->runProtected(function () use ($procurementClosure) {
            $closure = $this->service->submitForRequesterConfirmation($procurementClosure, Auth::user());
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Closure submitted for requester confirmation.',
                'data' => $closure,
            ]);
        });
    }

    public function close(Request $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('close', $procurementClosure);

        return $this->runProtected(function () use ($procurementClosure) {
            $closure = $this->service->close($procurementClosure, Auth::user());
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Procurement case closed successfully.',
                'data' => $closure,
            ]);
        });
    }

    public function closeWithException(CloseWithExceptionRequest $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('closeWithException', $procurementClosure);

        return $this->runProtected(function () use ($procurementClosure, $request) {
            $closure = $this->service->closeWithException(
                $procurementClosure,
                Auth::user(),
                $request->input('exception_reason'),
                $request->input('comments')
            );
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Procurement case closed with exception.',
                'data' => $closure,
            ]);
        });
    }

    public function cancelDraft(CancelProcurementClosureRequest $request, ProcurementClosure $procurementClosure): JsonResponse
    {
        $this->authorize('cancel', $procurementClosure);

        return $this->runProtected(function () use ($procurementClosure, $request) {
            $closure = $this->service->cancelDraft($procurementClosure, Auth::user(), $request->input('reason'));
            $this->loadRelations($closure);

            return response()->json([
                'message' => 'Closure draft cancelled.',
                'data' => $closure,
            ]);
        });
    }
}
