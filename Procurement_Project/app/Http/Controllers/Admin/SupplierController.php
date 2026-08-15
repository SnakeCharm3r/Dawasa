<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $suppliers = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return response()->json(['data' => $suppliers]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->validated());

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => $supplier,
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier->load('quotations');

        return response()->json(['data' => $supplier]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->quotations()->exists()) {
            return response()->json([
                'message' => 'Cannot delete supplier with associated quotations.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.',
        ]);
    }

    public function activate(Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => true]);

        return response()->json([
            'message' => 'Supplier activated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function deactivate(Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => false]);

        return response()->json([
            'message' => 'Supplier deactivated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }
}
