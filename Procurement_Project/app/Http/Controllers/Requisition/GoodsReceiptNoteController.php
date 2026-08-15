<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelGoodsReceiptNoteRequest;
use App\Http\Requests\StoreGoodsReceiptNoteRequest;
use App\Http\Requests\SubmitGoodsReceiptNoteRequest;
use App\Http\Requests\UpdateGoodsReceiptNoteRequest;
use App\Models\GoodsReceiptNote;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptNoteController extends Controller
{
    public function __construct(protected GoodsReceiptService $service)
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

    protected function loadRelations(GoodsReceiptNote $grn): void
    {
        $grn->load([
            'purchaseOrder',
            'supplier',
            'businessEntity',
            'receivedBy',
            'inspectedBy',
            'cancelledBy',
            'items.purchaseOrderItem',
            'approvals.actor',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceiptNote::class);

        $query = GoodsReceiptNote::with(['purchaseOrder', 'supplier', 'businessEntity', 'receivedBy']);

        if ($request->has('po_number')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('purchase_order_number', 'like', '%'.$request->input('po_number').'%'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from_date')) {
            $query->where('received_date', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('received_date', '<=', $request->input('to_date'));
        }

        if ($request->has('received_by')) {
            $query->where('received_by', $request->input('received_by'));
        }

        $grns = $query->orderByDesc('received_date')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $grns]);
    }

    public function show(GoodsReceiptNote $goodsReceiptNote): JsonResponse
    {
        $this->authorize('view', $goodsReceiptNote);

        $this->loadRelations($goodsReceiptNote);

        return response()->json(['data' => $goodsReceiptNote]);
    }

    public function store(StoreGoodsReceiptNoteRequest $request): JsonResponse
    {
        $this->authorize('create', GoodsReceiptNote::class);

        $order = PurchaseOrder::findOrFail($request->input('purchase_order_id'));

        return $this->runProtected(function () use ($order, $request) {
            $grn = $this->service->createDraftFromPurchaseOrder($order, Auth::user(), $request->validated());
            $grn->grn_number = $this->service->generateGrnNumber($grn);
            $grn->save();

            $this->loadRelations($grn);

            return response()->json([
                'message' => 'Goods Receipt Note draft created successfully.',
                'data' => $grn,
            ], 201);
        });
    }

    public function update(UpdateGoodsReceiptNoteRequest $request, GoodsReceiptNote $goodsReceiptNote): JsonResponse
    {
        $this->authorize('update', $goodsReceiptNote);

        return $this->runProtected(function () use ($goodsReceiptNote, $request) {
            $grn = $this->service->updateDraft($goodsReceiptNote, Auth::user(), $request->validated());
            $this->loadRelations($grn);

            return response()->json([
                'message' => 'Goods Receipt Note updated successfully.',
                'data' => $grn,
            ]);
        });
    }

    public function submit(SubmitGoodsReceiptNoteRequest $request, GoodsReceiptNote $goodsReceiptNote): JsonResponse
    {
        $this->authorize('submit', $goodsReceiptNote);

        return $this->runProtected(function () use ($goodsReceiptNote, $request) {
            $grn = $this->service->submit($goodsReceiptNote, Auth::user());
            $this->loadRelations($grn);

            return response()->json([
                'message' => 'Goods Receipt Note submitted for inspection.',
                'data' => $grn,
            ]);
        });
    }

    public function cancel(CancelGoodsReceiptNoteRequest $request, GoodsReceiptNote $goodsReceiptNote): JsonResponse
    {
        $this->authorize('cancel', $goodsReceiptNote);

        return $this->runProtected(function () use ($goodsReceiptNote, $request) {
            $grn = $this->service->cancel($goodsReceiptNote, Auth::user(), $request->input('cancellation_reason'));
            $this->loadRelations($grn);

            return response()->json([
                'message' => 'Goods Receipt Note cancelled.',
                'data' => $grn,
            ]);
        });
    }
}
