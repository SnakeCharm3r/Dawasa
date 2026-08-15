<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\InspectGoodsReceiptNoteRequest;
use App\Models\GoodsReceiptNote;
use App\Services\GoodsReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptInspectionController extends Controller
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

    public function inspect(InspectGoodsReceiptNoteRequest $request, GoodsReceiptNote $goodsReceiptNote): JsonResponse
    {
        $this->authorize('inspect', $goodsReceiptNote);

        return $this->runProtected(function () use ($goodsReceiptNote, $request) {
            $grn = $this->service->inspect($goodsReceiptNote, Auth::user(), $request->validated());
            $this->loadRelations($grn);

            return response()->json([
                'message' => 'Goods Receipt Note inspected successfully.',
                'data' => $grn,
            ]);
        });
    }
}
