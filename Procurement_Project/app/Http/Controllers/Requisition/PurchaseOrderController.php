<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\SubmitPurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Services\PurchaseOrderService;
use App\Services\EntityAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function __construct(protected PurchaseOrderService $service, private readonly EntityAccessService $entityAccess) {}

    protected function runProtected(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    protected function loadRelations(PurchaseOrder $order): void
    {
        $order->load([
            'supplier',
            'items' => fn ($query) => $query->with('quotationItem')->withSum('supplierInvoiceItems', 'quantity_invoiced'),
            'requisition.department.businessEntity',
            'businessEntity',
            'financialYear',
            'quotationRecommendation',
            'selectedQuotation',
            'approvals.actor',
            'accountantConfirmedBy',
            'issuedBy',
            'cancelledBy',
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::user();
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::with([
            'supplier',
            'items' => fn ($query) => $query->withSum('supplierInvoiceItems', 'quantity_invoiced'),
            'requisition',
            'selectedQuotation',
            'businessEntity',
        ]);

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            $query->whereHas('requisition', fn ($q) => $q
                ->where(fn ($owned) => $owned->where('line_manager_id', $user->id)->orWhere('requester_id', $user->id)))
                ->where('status', PurchaseOrder::STATUS_ISSUED);
        } elseif ($user->hasRole('requester')) {
            $query->whereHas('requisition', fn ($q) => $q->where('requester_id', $user->id))
                ->where('status', PurchaseOrder::STATUS_ISSUED);
        }

        $this->entityAccess->apply($query, $request, $user);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderByDesc('id')->paginate($request->input('per_page', 15));

        return PurchaseOrderResource::collection($orders);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        $this->loadRelations($purchaseOrder);

        return response()->json(['data' => new PurchaseOrderResource($purchaseOrder)]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $requisition = PurchaseRequisition::findOrFail($request->input('purchase_requisition_id'));
        abort_unless($this->entityAccess->canAccess($request->user(), $requisition->business_entity_id), 403);

        $operational = $request->only([
            'order_date', 'expected_delivery_date', 'currency',
            'payment_terms', 'delivery_terms', 'delivery_address', 'notes',
        ]);

        return $this->runProtected(function () use ($requisition, $operational) {
            $order = $this->service->createDraftFromRequisition($requisition, Auth::user(), $operational);
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Purchase order draft created successfully.',
                'data' => new PurchaseOrderResource($order),
            ], 201);
        });
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        $operational = $request->only([
            'order_date', 'expected_delivery_date', 'currency',
            'payment_terms', 'delivery_terms', 'delivery_address', 'notes',
        ]);

        return $this->runProtected(function () use ($purchaseOrder, $operational) {
            $order = $this->service->updateDraft($purchaseOrder, Auth::user(), $operational);
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Purchase order updated successfully.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function submitForConfirmation(SubmitPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('submitForConfirmation', $purchaseOrder);

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->submitForConfirmation($purchaseOrder, Auth::user(), $request->input('comments'));
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Purchase order submitted for accountant confirmation.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function issue(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('issue', $purchaseOrder);

        return $this->runProtected(function () use ($purchaseOrder) {
            $order = $this->service->issue($purchaseOrder, Auth::user());
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Purchase order issued.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->cancel($purchaseOrder, Auth::user(), $request->input('cancellation_reason'));
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Purchase order cancelled.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function acknowledge(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('acknowledge', $purchaseOrder);

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->acknowledge($purchaseOrder, Auth::user(), $request->input('acknowledgement_reference'));
            $this->loadRelations($order);

            return response()->json([
                'message' => 'Supplier acknowledgement recorded.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }
}
