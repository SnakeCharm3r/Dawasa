<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPaymentVoucherRequest;
use App\Http\Requests\StorePaymentVoucherRequest;
use App\Http\Requests\SubmitPaymentVoucherRequest;
use App\Http\Requests\UpdatePaymentVoucherRequest;
use App\Models\PaymentVoucher;
use App\Models\SupplierInvoice;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentVoucherController extends Controller
{
    public function __construct(protected PaymentService $service) {}

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
            'supplierInvoice.purchaseOrder.requisition',
            'supplier',
            'businessEntity',
            'financialYear',
            'preparedBy',
            'approvedBy',
            'paidBy',
            'approvals.actor',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentVoucher::class);

        $query = PaymentVoucher::with(['supplierInvoice', 'supplier', 'businessEntity', 'preparedBy']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->has('from_date')) {
            $query->where('payment_date', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('payment_date', '<=', $request->input('to_date'));
        }

        $vouchers = $query->orderByDesc('created_at')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $vouchers]);
    }

    public function show(PaymentVoucher $paymentVoucher): JsonResponse
    {
        $this->authorize('view', $paymentVoucher);

        $this->loadRelations($paymentVoucher);

        return response()->json(['data' => $paymentVoucher]);
    }

    public function store(StorePaymentVoucherRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentVoucher::class);

        return $this->runProtected(function () use ($request) {
            $voucher = $this->service->createVoucherFromInvoice(
                SupplierInvoice::findOrFail($request->input('supplier_invoice_id')),
                Auth::user(),
                $request->validated()
            );

            $this->loadRelations($voucher);

            return response()->json([
                'message' => 'Payment voucher draft created successfully.',
                'data' => $voucher,
            ], 201);
        });
    }

    public function update(UpdatePaymentVoucherRequest $request, PaymentVoucher $paymentVoucher): JsonResponse
    {
        $this->authorize('update', $paymentVoucher);

        return $this->runProtected(function () use ($paymentVoucher, $request) {
            $paymentVoucher->update($request->validated());
            $this->loadRelations($paymentVoucher);

            return response()->json([
                'message' => 'Payment voucher updated successfully.',
                'data' => $paymentVoucher,
            ]);
        });
    }

    public function submit(SubmitPaymentVoucherRequest $request, PaymentVoucher $paymentVoucher): JsonResponse
    {
        $this->authorize('submit', $paymentVoucher);

        return $this->runProtected(function () use ($paymentVoucher) {
            $voucher = $this->service->submitVoucher($paymentVoucher, Auth::user());
            $this->loadRelations($voucher);

            return response()->json([
                'message' => 'Payment voucher submitted for approval.',
                'data' => $voucher,
            ]);
        });
    }

    public function cancel(CancelPaymentVoucherRequest $request, PaymentVoucher $paymentVoucher): JsonResponse
    {
        $this->authorize('cancel', $paymentVoucher);

        return $this->runProtected(function () use ($paymentVoucher, $request) {
            $voucher = $this->service->cancelVoucher($paymentVoucher, Auth::user(), $request->input('cancellation_reason'));
            $this->loadRelations($voucher);

            return response()->json([
                'message' => 'Payment voucher cancelled.',
                'data' => $voucher,
            ]);
        });
    }
}
