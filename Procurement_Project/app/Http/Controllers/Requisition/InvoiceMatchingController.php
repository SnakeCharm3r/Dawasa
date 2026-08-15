<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceVarianceDecisionRequest;
use App\Http\Requests\MatchSupplierInvoiceRequest;
use App\Models\SupplierInvoice;
use App\Services\ThreeWayMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceMatchingController extends Controller
{
    public function __construct(protected ThreeWayMatchingService $service)
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

    protected function loadRelations(SupplierInvoice $invoice): void
    {
        $invoice->load([
            'purchaseOrder',
            'supplier',
            'businessEntity',
            'financialYear',
            'submittedBy',
            'approvedBy',
            'items.purchaseOrderItem',
            'matchRecords.matchedBy',
            'paymentVouchers',
        ]);
    }

    public function match(MatchSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('match', $supplierInvoice);

        return $this->runProtected(function () use ($supplierInvoice) {
            $matchRecord = $this->service->performThreeWayMatch($supplierInvoice, Auth::user());
            $this->loadRelations($supplierInvoice);

            return response()->json([
                'message' => 'Invoice matched successfully.',
                'data' => $supplierInvoice,
                'match_record' => $matchRecord,
            ]);
        });
    }

    public function varianceDecision(InvoiceVarianceDecisionRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->authorize('approveVariance', $supplierInvoice);

        return $this->runProtected(function () use ($supplierInvoice, $request) {
            if ($request->input('decision') === 'approve') {
                $invoice = $this->service->approveVariance($supplierInvoice, Auth::user());
            } else {
                $invoice = $this->service->returnForCorrection($supplierInvoice, Auth::user(), $request->input('reason'));
            }

            $this->loadRelations($invoice);

            return response()->json([
                'message' => $request->input('decision') === 'approve' ? 'Variance approved.' : 'Invoice returned for correction.',
                'data' => $invoice,
            ]);
        });
    }
}
