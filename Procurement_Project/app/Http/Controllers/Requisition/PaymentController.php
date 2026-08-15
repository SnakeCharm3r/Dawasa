<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Models\PaymentVoucher;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $service)
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

    protected function loadRelations(PaymentVoucher $voucher): void
    {
        $voucher->load([
            'supplierInvoice',
            'supplier',
            'businessEntity',
            'financialYear',
            'preparedBy',
            'approvedBy',
            'paidBy',
            'approvals.actor',
        ]);
    }

    public function record(RecordPaymentRequest $request, PaymentVoucher $paymentVoucher): JsonResponse
    {
        $this->authorize('recordPayment', $paymentVoucher);

        return $this->runProtected(function () use ($paymentVoucher, $request) {
            $voucher = $this->service->recordPayment($paymentVoucher, Auth::user(), $request->validated());
            $this->loadRelations($voucher);

            return response()->json([
                'message' => 'Payment recorded successfully.',
                'data' => $voucher,
            ]);
        });
    }
}
