<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tender;
use App\Models\TenderResponse;
use App\Models\TenderResponseDocument;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenderResponseController extends Controller
{
    public function __construct(private readonly SupplierComplianceService $compliance) {}

    public function index(Request $request): JsonResponse
    {
        $supplier = $this->supplier($request);
        return response()->json(['data' => $supplier->tenderResponses()->with('tender:id,tender_number,title,submission_deadline,status')->latest()->paginate(15)]);
    }

    public function show(Request $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->owns($request, $tenderResponse);
        return response()->json(['data' => $tenderResponse->load(['tender.items', 'items.tenderItem', 'documents'])]);
    }

    public function store(Request $request, Tender $tender): JsonResponse
    {
        $supplier = $this->supplier($request);
        abort_if($tender->submission_deadline->isPast(), 422, 'The submission deadline has passed.');
        $this->assertEligible($tender, $supplier);
        $existing = $tender->responses()->where('supplier_id', $supplier->id)->first();
        abort_if($existing && $existing->status !== 'draft', 422, 'You already submitted a response for this tender.');
        $data = $this->validateResponse($request, $tender);

        $response = DB::transaction(function () use ($data, $tender, $supplier, $existing) {
            $items = collect($data['items']);
            $subtotal = $items->sum(function ($item) use ($tender) {
                $quantity = (float) $tender->items->firstWhere('id', $item['tender_item_id'])->quantity;
                return $quantity * (float) $item['unit_price'];
            });
            $attributes = [
                'supplier_id' => $supplier->id, ...collect($data)->except('items')->all(),
                'subtotal' => $subtotal, 'total_amount' => $subtotal + (float) ($data['tax_amount'] ?? 0), 'status' => 'draft',
            ];
            if ($existing) {
                $existing->update($attributes);
                $existing->items()->delete();
                $response = $existing;
            } else {
                $response = $tender->responses()->create($attributes);
            }
            $response->items()->createMany($items->map(function ($item) use ($tender) {
                $quantity = (float) $tender->items->firstWhere('id', $item['tender_item_id'])->quantity;
                return [...$item, 'line_total' => $quantity * (float) $item['unit_price']];
            })->all());
            return $response;
        });
        return response()->json(['message' => 'Tender response saved as draft.', 'data' => $response->load('items')], 201);
    }

    public function update(Request $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->owns($request, $tenderResponse);
        abort_unless($tenderResponse->status === 'draft', 422, 'Only draft responses can be edited.');
        abort_if($tenderResponse->tender->submission_deadline->isPast(), 422, 'The submission deadline has passed.');
        $data = $this->validateResponse($request, $tenderResponse->tender);
        DB::transaction(function () use ($tenderResponse, $data) {
            $items = collect($data['items']);
            $subtotal = $items->sum(fn ($item) => (float) $tenderResponse->tender->items->firstWhere('id', $item['tender_item_id'])->quantity * (float) $item['unit_price']);
            $tenderResponse->update([...collect($data)->except('items')->all(), 'subtotal' => $subtotal, 'total_amount' => $subtotal + (float) ($data['tax_amount'] ?? 0)]);
            $tenderResponse->items()->delete();
            $tenderResponse->items()->createMany($items->map(fn ($item) => [...$item, 'line_total' => (float) $tenderResponse->tender->items->firstWhere('id', $item['tender_item_id'])->quantity * (float) $item['unit_price']])->all());
        });
        return response()->json(['message' => 'Draft response updated.', 'data' => $tenderResponse->fresh('items')]);
    }

    public function submit(Request $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->owns($request, $tenderResponse);
        abort_unless($tenderResponse->status === 'draft', 422, 'Only draft responses can be submitted.');
        abort_if($tenderResponse->tender->submission_deadline->isPast(), 422, 'The submission deadline has passed.');
        abort_unless($tenderResponse->documents()->where('document_type', 'proforma')->exists(), 422, 'Upload the signed proforma or quotation before submission.');
        $receipt = DB::transaction(function () use ($request, $tenderResponse) {
            $number = sprintf('BID-%d-%05d', now()->year, TenderResponse::whereYear('created_at', now()->year)->lockForUpdate()->count() + 1);
            $tenderResponse->update(['status' => 'submitted', 'receipt_number' => $number, 'submitted_at' => now()]);
            ActivityLog::record($request->user(), 'tender_response.submitted', $tenderResponse, ['status' => 'draft'], ['status' => 'submitted', 'receipt_number' => $number]);
            return $number;
        });
        return response()->json(['message' => 'Quotation submitted and locked.', 'data' => ['receipt_number' => $receipt, 'submitted_at' => $tenderResponse->fresh()->submitted_at]]);
    }

    public function storeDocument(Request $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->owns($request, $tenderResponse);
        abort_unless($tenderResponse->status === 'draft', 422, 'Documents can only be added to a draft response.');
        abort_if($tenderResponse->tender->submission_deadline->isPast(), 422, 'The submission deadline has passed.');
        $data = $request->validate([
            'document_type' => ['required', 'in:proforma,catalogue,technical,warranty,compliance,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $data['file'];
        $document = $tenderResponse->documents()->create([
            'document_type' => $data['document_type'], 'original_name' => $file->getClientOriginalName(),
            'storage_path' => $file->store('tender-responses/'.$tenderResponse->id, 'local'),
            'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
        ]);
        return response()->json(['message' => 'Response document uploaded securely.', 'data' => $document], 201);
    }

    public function downloadDocument(Request $request, TenderResponseDocument $document)
    {
        $this->owns($request, $document->response);
        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    private function validateResponse(Request $request, Tender $tender): array
    {
        $tender->loadMissing('items');
        $data = $request->validate([
            'quotation_number' => ['required', 'string', 'max:50'], 'quotation_date' => ['required', 'date', 'before_or_equal:today'],
            'valid_until' => ['nullable', 'date', 'after:today'], 'currency' => ['required', 'in:TZS'],
            'delivery_period_days' => ['nullable', 'integer', 'min:0'], 'warranty_terms' => ['nullable', 'string', 'max:5000'],
            'supplier_comments' => ['nullable', 'string', 'max:5000'], 'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'size:'.$tender->items->count()], 'items.*.tender_item_id' => ['required', 'integer', 'distinct'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'], 'items.*.brand_make' => ['nullable', 'string', 'max:255'],
            'items.*.offered_specification' => ['nullable', 'string', 'max:5000'],
        ]);
        $expected = $tender->items->pluck('id')->sort()->values()->all();
        $actual = collect($data['items'])->pluck('tender_item_id')->sort()->values()->all();
        abort_unless($expected === $actual, 422, 'Pricing must be supplied for every tender item and no other items.');
        return $data;
    }

    private function supplier(Request $request) { abort_unless($request->user()->hasRole('supplier'), 403); return $request->user()->supplier()->firstOrFail(); }
    private function owns(Request $request, TenderResponse $response): void { abort_unless($response->supplier_id === $this->supplier($request)->id, 403); }
    private function assertEligible(Tender $tender, $supplier): void
    {
        abort_unless($tender->status === Tender::STATUS_PUBLISHED, 422, 'Tender is not open.');
        $categoryEligible = $supplier->categories()->whereKey($tender->supplier_category_id)->exists();
        $invited = $tender->invitedSuppliers()->whereKey($supplier->id)->exists();
        abort_unless(($tender->visibility === 'public' && $categoryEligible) || $invited, 403);
        abort_unless($this->compliance->canParticipate($supplier), 403, 'Supplier compliance currently prevents tender response or award.');
    }
}
