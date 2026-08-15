<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVoucherDecisionRequest;
use App\Models\PaymentVoucher;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentApprovalController extends Controller
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

    public function decision(PaymentVoucherDecisionRequest $request, PaymentVoucher $paymentVoucher): JsonResponse
    {
        $decision = $request->input('decision');

        if ($decision === 'approve') {
            $this->authorize('approve', $paymentVoucher);
            return $this->runProtected(function () use ($paymentVoucher, $request) {
                $voucher = $this->service->approveVoucher($paymentVoucher, Auth::user(), $request->input('comments'));
                $this->loadRelations($voucher);

                return response()->json([
                    'message' => 'Payment voucher approved.',
                    'data' => $voucher,
                ]);
            });
        } elseif ($decision === 'return') {
            $this->authorize('return', $paymentVoucher);
            return $this->runProtected(function () use ($paymentVoucher, $request) {
                $voucher = $this->service->returnVoucher($paymentVoucher, Auth::user(), $request->input('reason'));
                $this->loadRelations($voucher);

                return response()->json([
                    'message' => 'Payment voucher returned for correction.',
                    'data' => $voucher,
                ]);
            });
        } else {
            $this->authorize('reject', $paymentVoucher);
            return $this->runProtected(function () use ($paymentVoucher, $request) {
                $voucher = $this->service->rejectVoucher($paymentVoucher, Auth::user(), $request->input('reason'));
                $this->loadRelations($voucher);

                return response()->json([
                    'message' => 'Payment voucher rejected.',
                    'data' => $voucher,
                ]);
            });
        }
    }
}
