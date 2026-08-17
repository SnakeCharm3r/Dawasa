<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierQuotationRequest;
use App\Http\Requests\UpdateSupplierQuotationRequest;
use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Services\EntityAccessService;
use App\Services\QuotationRecommendationService;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierQuotationController extends Controller
{
    public function __construct(
        private readonly QuotationRecommendationService $recommendationService,
        private readonly EntityAccessService $entityAccess,
        private readonly SupplierComplianceService $supplierCompliance,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupplierQuotation::class);

        $query = SupplierQuotation::with(['supplier', 'requisition', 'approvalRecommendation']);
        $user = Auth::user();

        if ($user->hasAnyRole(['line_manager', 'department_head'])) {
            $query->whereHas('requisition', fn ($requisition) => $requisition
                ->where(fn ($owned) => $owned->where('line_manager_id', $user->id)->orWhere('requester_id', $user->id)));
        } elseif ($user->hasRole('requester')) {
            $query->whereHas('requisition', fn ($requisition) => $requisition->where('requester_id', $user->id));
        }

        $this->entityAccess->apply($query, $request, $user, 'requisition');

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
        $supplier = Supplier::findOrFail($request->input('supplier_id'));
        abort_unless($this->entityAccess->canAccess($request->user(), $requisition->business_entity_id), 403);
        abort_unless($this->supplierCompliance->canParticipate($supplier), 422, 'This supplier is not currently eligible for sourcing or award.');
        abort_unless($supplier->categories()->whereKey($requisition->supplier_category_id)->exists(), 422, 'This supplier is not approved for the requisition category.');

        if (! in_array($requisition->status, [
            PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
            PurchaseRequisition::STATUS_QUOTATIONS_READY,
        ], true)) {
            return response()->json([
                'message' => 'Quotations can only be added to requisitions approved for sourcing.',
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = collect($data['items']);
            $total = $items->sum(fn (array $item) => (float) $item['quantity'] * (float) $item['unit_price']);

            $quotation = SupplierQuotation::create([
                ...collect($data)->except('items')->all(),
                'prepared_by' => Auth::id(),
                'total_amount' => $total,
                'status' => SupplierQuotation::STATUS_DRAFT,
            ]);

            $quotation->items()->createMany($items->map(fn (array $item) => [
                ...$item,
                'total_price' => (float) $item['quantity'] * (float) $item['unit_price'],
            ])->all());

            return response()->json([
                'message' => 'Supplier proforma created successfully.',
                'data' => $quotation->load(['supplier', 'requisition', 'items']),
            ], 201);
        });
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

    public function reject(Request $request, SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('reject', $supplierQuotation);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $supplierQuotation->update([
            'status' => SupplierQuotation::STATUS_REJECTED,
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'rejection_reason' => $data['reason'],
        ]);

        ActivityLog::record(
            Auth::user(),
            'supplier_quotation.rejected',
            $supplierQuotation,
            ['status' => SupplierQuotation::STATUS_ACTIVE],
            ['status' => SupplierQuotation::STATUS_REJECTED, 'reason' => $data['reason']],
        );

        return response()->json([
            'message' => 'Proforma rejected.',
            'data' => $supplierQuotation->fresh(['supplier', 'requisition', 'rejectedBy']),
        ]);
    }

    public function requestApproval(Request $request, SupplierQuotation $supplierQuotation): JsonResponse
    {
        $this->authorize('update', $supplierQuotation);

        $data = $request->validate([
            'reason_for_selection' => ['required', 'string', 'max:2000'],
            'non_lowest_price_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $recommendation = $this->recommendationService->submitProformaForApproval(
                $supplierQuotation,
                $data,
                Auth::user(),
            );
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Proforma sent for GM approval.',
            'data' => $supplierQuotation->fresh(['supplier', 'requisition', 'approvalRecommendation']),
            'recommendation' => $recommendation,
        ]);
    }
}
