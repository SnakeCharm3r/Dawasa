<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuotationRecommendationRequest;
use App\Http\Requests\SubmitQuotationRecommendationRequest;
use App\Http\Requests\UpdateQuotationRecommendationRequest;
use App\Http\Resources\PurchaseRequisitionResource;
use App\Http\Resources\QuotationRecommendationResource;
use App\Models\PurchaseRequisition;
use App\Models\QuotationRecommendation;
use App\Services\QuotationRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuotationRecommendationController extends Controller
{
    private QuotationRecommendationService $service;

    public function __construct(QuotationRecommendationService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QuotationRecommendation::class);

        $query = QuotationRecommendation::with(['selectedQuotation.supplier', 'recommendedBy', 'requisition']);
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'accountant', 'gm', 'auditor'])) {
            // full access
        } elseif ($user->hasRole('procurement_officer')) {
            $query->whereHas('requisition', function ($query) {
                $query->whereIn('status', [
                    PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING,
                    PurchaseRequisition::STATUS_QUOTATIONS_READY,
                    PurchaseRequisition::STATUS_RETURNED_TO_SOURCING,
                ]);
            });
        } elseif ($user->hasRole('department_head')) {
            $query->whereHas('requisition', fn ($query) => $query->where('department_id', $user->department_id));
        } else {
            $query->where('recommended_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $recommendations = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json(QuotationRecommendationResource::collection($recommendations));
    }

    public function show(QuotationRecommendation $quotationRecommendation): JsonResponse
    {
        $this->authorize('view', $quotationRecommendation);
        $quotationRecommendation->load(['selectedQuotation.supplier', 'recommendedBy', 'requisition', 'procurementApprovals.actor']);

        return response()->json(new QuotationRecommendationResource($quotationRecommendation));
    }

    public function createRecommendation(PurchaseRequisition $purchaseRequisition, StoreQuotationRecommendationRequest $request): JsonResponse
    {
        $this->authorize('create', [QuotationRecommendation::class, $purchaseRequisition]);
        $recommendation = $this->service->createDraft($purchaseRequisition, $request->validated(), Auth::user());

        return response()->json(['message' => 'Quotation recommendation draft created successfully.', 'data' => new QuotationRecommendationResource($recommendation)], 201);
    }

    public function update(PurchaseRequisition $purchaseRequisition, QuotationRecommendation $quotationRecommendation, UpdateQuotationRecommendationRequest $request): JsonResponse
    {
        $this->authorize('update', $quotationRecommendation);
        $recommendation = $this->service->updateDraft($quotationRecommendation, $request->validated());

        return response()->json(['message' => 'Quotation recommendation draft updated successfully.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function submit(PurchaseRequisition $purchaseRequisition, QuotationRecommendation $quotationRecommendation, SubmitQuotationRecommendationRequest $request): JsonResponse
    {
        $this->authorize('submit', $quotationRecommendation);
        $recommendation = $this->service->submit($quotationRecommendation);

        return response()->json(['message' => 'Quotation recommendation submitted successfully.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function compare(PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        $this->authorize('view', $purchaseRequisition);
        $purchaseRequisition->load(['supplierQuotations.supplier', 'items']);

        $supplierQuotations = $purchaseRequisition->supplierQuotations()
            ->valid()
            ->with('supplier')
            ->get()
            ->map(fn ($quotation) => [
                'id' => $quotation->id,
                'supplier' => [
                    'id' => $quotation->supplier?->id,
                    'name' => $quotation->supplier?->name,
                ],
                'total_amount' => $quotation->total_amount,
                'status' => $quotation->status,
                'valid_until' => $quotation->valid_until?->toDateString(),
                'items' => $quotation->items->map(fn ($item) => [
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]),
            ]);

        return response()->json([
            'requisition' => new PurchaseRequisitionResource($purchaseRequisition),
            'quotations' => $supplierQuotations,
            'lowest_valid_amount' => $purchaseRequisition->supplierQuotations()->valid()->min('total_amount'),
        ]);
    }
}
