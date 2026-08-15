<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderConfirmationRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderConfirmationController extends Controller
{
    public function __construct(protected PurchaseOrderService $service) {}

    protected function runProtected(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function confirm(PurchaseOrderConfirmationRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('confirm', $purchaseOrder);

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->confirm($purchaseOrder, Auth::user(), $request->input('comments'));

            return response()->json([
                'message' => 'Purchase order confirmed by accountant.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function returnToProcurement(PurchaseOrderConfirmationRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('returnToProcurement', $purchaseOrder);

        if (empty($request->input('comments'))) {
            return response()->json(['message' => 'Return comments are required.'], 422);
        }

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->returnToProcurement($purchaseOrder, Auth::user(), $request->input('comments'));

            return response()->json([
                'message' => 'Purchase order returned to procurement.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }

    public function reject(PurchaseOrderConfirmationRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('reject', $purchaseOrder);

        if (empty($request->input('comments'))) {
            return response()->json(['message' => 'A rejection reason is required.'], 422);
        }

        return $this->runProtected(function () use ($purchaseOrder, $request) {
            $order = $this->service->reject($purchaseOrder, Auth::user(), $request->input('comments'));

            return response()->json([
                'message' => 'LPO rejected by accountant.',
                'data' => new PurchaseOrderResource($order),
            ]);
        });
    }
}
