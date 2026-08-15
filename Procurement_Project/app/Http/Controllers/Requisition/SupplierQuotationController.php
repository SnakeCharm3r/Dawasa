<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierQuotationRequest;
use App\Http\Requests\UpdateSupplierQuotationRequest;
use App\Models\PurchaseRequisition;
use App\Models\SupplierQuotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierQuotationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupplierQuotation::class);

        $query = SupplierQuotation::with(['supplier', 'requisition']);

        if ($request->has('requisition_id')) {
            $query->where('purchase_requisition_id', $request->input('requisition_id'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $quotations = $query->orderByDesc('created_at')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $quotations]);
    }

    public function store(StoreSupplierQuotationRequest $request): JsonResponse
    {
        $this->authorize('create', SupplierQuotation::class);

        $requisition = PurchaseRequisition::findOrFail($request->input('purchase_requisition_id'));

        if (! in_array($requisition->status, [
            PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
            PurchaseRequisition::STATUS_QUOTATIONS_READY,
        ], true)) {
            return response()->json([
                'message' => 'Quotations can only be added to requisitions approved for sourcing.',
            ], 422);
        }

        $quotation = SupplierQuotation::create($request->validated());

        return response()->json([
            'message' => 'Supplier quotation created successfully.',
            'data' => $quotation->load('items'),
        ], 201);
    }

    public function show(SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('view', $supplierQuotation);

        $supplierQuotation->load(['supplier', 'requisition', 'items']);

        return response()->json(['data' => $supplierQuotation]);
    }

    public function update(UpdateSupplierQuotationRequest $request, SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('update', $supplierQuotation);

        if ($supplierQuotation->status !== SupplierQuotation::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft quotations can be updated.',
            ], 422);
        }

        $supplierQuotation->update($request->validated());

        return response()->json([
            'message' => 'Supplier quotation updated successfully.',
            'data' => $supplierQuotation->fresh()->load('items'),
        ]);
    }

    public function destroy(SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('delete', $supplierQuotation);

        if ($supplierQuotation->status !== SupplierQuotation::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft quotations can be deleted.',
            ], 422);
        }

        $supplierQuotation->delete();

        return response()->json([
            'message' => 'Supplier quotation deleted successfully.',
        ]);
    }

    public function submit(SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('update', $supplierQuotation);

        if ($supplierQuotation->status !== SupplierQuotation::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft quotations can be submitted.',
            ], 422);
        }

        $supplierQuotation->update([
            'status' => SupplierQuotation::STATUS_ACTIVE,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Supplier quotation submitted successfully.',
            'data' => $supplierQuotation->fresh(),
        ]);
    }

    public function withdraw(SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('update', $supplierQuotation);

        if ($supplierQuotation->status !== SupplierQuotation::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Only active quotations can be withdrawn.',
            ], 422);
        }

        $supplierQuotation->update([
            'status' => SupplierQuotation::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        return response()->json([
            'message' => 'Supplier quotation withdrawn successfully.',
            'data' => $supplierQuotation->fresh(),
        ]);
    }
}
