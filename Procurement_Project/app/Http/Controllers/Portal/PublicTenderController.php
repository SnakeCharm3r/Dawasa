<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SupplierCategory;
use App\Models\Tender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTenderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tender::publiclyOpen()->with('category:id,name,code');
        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
            ->where('title', 'like', '%'.$request->string('search').'%')
            ->orWhere('tender_number', 'like', '%'.$request->string('search').'%')));
        $query->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('code', $request->string('category'))));
        $query->when($request->filled('type'), fn ($q) => $q->where('tender_type', $request->string('type')));

        $page = $query->orderBy('submission_deadline')->paginate(min($request->integer('per_page', 12), 50));
        $page->through(fn (Tender $tender) => $this->summaryPayload($tender));

        return response()->json(['data' => $page]);
    }

    public function show(string $tenderNumber): JsonResponse
    {
        $tender = Tender::query()->with(['category:id,name,code', 'items:id,tender_id,item_name,specification,quantity,unit'])
            ->where('tender_number', $tenderNumber)->where('visibility', 'public')
            ->whereIn('status', [Tender::STATUS_PUBLISHED, Tender::STATUS_CLOSED, Tender::STATUS_AWARDED])->firstOrFail();

        return response()->json(['data' => $this->publicPayload($tender)]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => SupplierCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])]);
    }

    private function publicPayload(Tender $tender): array
    {
        return [
            'id' => $tender->id, 'tender_number' => $tender->tender_number, 'title' => $tender->title,
            'public_summary' => $tender->public_summary, 'tender_type' => $tender->tender_type,
            'status' => $tender->submission_deadline->isPast() ? 'closed' : $tender->status,
            'publication_at' => $tender->publication_at, 'submission_deadline' => $tender->submission_deadline,
            'expected_delivery_date' => $tender->expected_delivery_date, 'delivery_location' => $tender->delivery_location,
            'contact_email' => $tender->contact_email, 'contact_phone' => $tender->contact_phone,
            'eligibility_requirements' => $tender->eligibility_requirements,
            'submission_instructions' => $tender->submission_instructions,
            'terms_and_conditions' => $tender->terms_and_conditions,
            'category' => $tender->category, 'items' => $tender->items,
        ];
    }

    private function summaryPayload(Tender $tender): array
    {
        return [
            'id' => $tender->id, 'tender_number' => $tender->tender_number, 'title' => $tender->title,
            'public_summary' => $tender->public_summary, 'tender_type' => $tender->tender_type,
            'status' => $tender->submission_deadline->isPast() ? 'closed' : $tender->status,
            'publication_at' => $tender->publication_at, 'submission_deadline' => $tender->submission_deadline,
            'category' => $tender->category,
        ];
    }
}
