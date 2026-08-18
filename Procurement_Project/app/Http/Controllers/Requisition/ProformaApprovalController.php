<?php

namespace App\Http\Controllers\Requisition;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProformaApprovalRequest;
use App\Http\Resources\QuotationRecommendationResource;
use App\Models\QuotationRecommendation;
use App\Services\QuotationRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProformaApprovalController extends Controller
{
    public function __construct(private readonly QuotationRecommendationService $service)
    {
        $this->middleware('auth');
    }

    public function requesterSubmit(QuotationRecommendation $quotationRecommendation, ProformaApprovalRequest $request): JsonResponse
    {
        $this->authorize('requesterSubmit', $quotationRecommendation);

        try {
            $recommendation = $this->service->submit($quotationRecommendation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Quotation recommendation submitted for line manager review.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function requesterReturn(QuotationRecommendation $quotationRecommendation, ProformaApprovalRequest $request): JsonResponse
    {
        $this->authorize('requesterReturn', $quotationRecommendation);

        $recommendation = $this->service->requesterReturn($quotationRecommendation, $request->input('comments'), Auth::user());

        return response()->json(['message' => 'Quotation recommendation returned to sourcing.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function lineManagerApprove(QuotationRecommendation $quotationRecommendation, ProformaApprovalRequest $request): JsonResponse
    {
        $this->authorize('lineManagerApprove', $quotationRecommendation);

        try {
            $recommendation = $this->service->lineManagerApprove($quotationRecommendation, $request->input('comments'), Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Quotation recommendation approved for final GM review.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }

    public function lineManagerReturn(QuotationRecommendation $quotationRecommendation, ProformaApprovalRequest $request): JsonResponse
    {
        $this->authorize('lineManagerReturn', $quotationRecommendation);

        $recommendation = $this->service->lineManagerReturn($quotationRecommendation, $request->input('comments'), Auth::user());

        return response()->json(['message' => 'Quotation recommendation returned to requester for review.', 'data' => new QuotationRecommendationResource($recommendation)]);
    }
}
