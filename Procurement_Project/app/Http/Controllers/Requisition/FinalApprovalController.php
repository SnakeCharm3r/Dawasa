<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinalProcurementApprovalRequest;
use App\Http\Resources\QuotationRecommendationResource;
use App\Models\QuotationRecommendation;
use App\Services\QuotationRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FinalApprovalController extends Controller
{
    private QuotationRecommendationService $service;

    public function __construct(QuotationRecommendationService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
    }

    public function approve(QuotationRecommendation $quotationRecommendation, FinalProcurementApprovalRequest $request): JsonResponse
    {
        $this->authorize('approve', $quotationRecommendation);

        try {
            $recommendation = $this->service->approve($quotationRecommendation, $request->input('comments'), Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Quotation recommendation approved for purchase.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function returnToSourcing(QuotationRecommendation $quotationRecommendation, FinalProcurementApprovalRequest $request): JsonResponse
    {
        $this->authorize('returnToSourcing', $quotationRecommendation);

        $recommendation = $this->service->returnToSourcing($quotationRecommendation, $request->input('comments'), Auth::user());

        return response()->json(['message' => 'Quotation recommendation returned to sourcing.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function reject(QuotationRecommendation $quotationRecommendation, FinalProcurementApprovalRequest $request): JsonResponse
    {
        $this->authorize('reject', $quotationRecommendation);

        $recommendation = $this->service->reject($quotationRecommendation, $request->input('comments'), Auth::user());

        return response()->json(['message' => 'Quotation recommendation rejected.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }
}
