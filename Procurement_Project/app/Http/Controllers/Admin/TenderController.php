<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\Tender;
use App\Models\TenderResponse;
use App\Models\TenderResponseDocument;
use App\Notifications\TenderAwardedNotification;
use App\Services\EntityAccessService;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenderController extends Controller
{
    public function __construct(
        private readonly SupplierComplianceService $compliance,
        private readonly EntityAccessService $entityAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->internal($request);
        $query = Tender::with(['category:id,name,code', 'requisition:id,requisition_number,status,business_entity_id'])
            ->withCount(['responses', 'responses as submitted_responses_count' => fn ($responses) => $responses->where('status', '!=', 'draft')]);
        $this->entityAccess->apply($query, $request, $request->user(), 'requisition');
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($i) => $i->where('title', 'like', '%'.$request->string('search').'%')->orWhere('tender_number', 'like', '%'.$request->string('search').'%')));

        return response()->json(['data' => $query->latest()->paginate(min($request->integer('per_page', 15), 50))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->internal($request);
        $data = $this->validateTender($request);
        $requisition = PurchaseRequisition::with('items')->findOrFail($data['purchase_requisition_id']);
        abort_unless(in_array($requisition->status, [PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, PurchaseRequisition::STATUS_RETURNED_TO_SOURCING], true), 422, 'Requisition is not available for sourcing.');
        abort_if(Tender::where('purchase_requisition_id', $requisition->id)->whereNotIn('status', [Tender::STATUS_CANCELLED])->exists(), 422, 'An active tender already exists for this requisition.');
        $tender = DB::transaction(function () use ($data, $requisition, $request) {
            $number = sprintf('RFQ-%d-%06d', now()->year, Tender::whereYear('created_at', now()->year)->lockForUpdate()->count() + 1);
            $tender = Tender::create([...$data, 'tender_number' => $number, 'created_by' => $request->user()->id, 'status' => Tender::STATUS_DRAFT]);
            $tender->items()->createMany($requisition->items->map(fn ($item) => [
                'purchase_requisition_item_id' => $item->id, 'item_name' => $item->item_name,
                'specification' => $item->specification, 'quantity' => $item->quantity, 'unit' => $item->unit,
            ])->all());
            ActivityLog::record($request->user(), 'tender.created', $tender, [], ['status' => 'draft']);

            return $tender;
        });

        return response()->json(['message' => 'Tender draft created from public-safe requisition fields.', 'data' => $tender->load('items')], 201);
    }

    public function storeFromOtherSuppliers(Request $request, PurchaseRequisition $purchaseRequisition): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'ceo']), 403);
        abort_unless($this->entityAccess->canAccess($request->user(), $purchaseRequisition->business_entity_id), 403);
        abort_unless(in_array($purchaseRequisition->status, [PurchaseRequisition::STATUS_APPROVED_FOR_SOURCING, PurchaseRequisition::STATUS_RETURNED_TO_SOURCING], true), 422, 'Requisition is not available for sourcing.');
        abort_unless($purchaseRequisition->supplier_category_id, 422, 'Assign a supplier category to the requisition before requesting public bids.');
        abort_if(Tender::where('purchase_requisition_id', $purchaseRequisition->id)->whereNotIn('status', [Tender::STATUS_CANCELLED])->exists(), 422, 'An active tender already exists for this requisition.');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'public_summary' => ['required', 'string', 'max:5000'],
            'submission_deadline' => ['required', 'date', 'after:now'],
            'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'eligibility_requirements' => ['nullable', 'string', 'max:10000'],
            'submission_instructions' => ['nullable', 'string', 'max:10000'],
            'terms_and_conditions' => ['nullable', 'string', 'max:10000'],
        ]);

        $purchaseRequisition->load('items');
        $tender = DB::transaction(function () use ($data, $purchaseRequisition, $request) {
            $number = sprintf('RFQ-%d-%06d', now()->year, Tender::whereYear('created_at', now()->year)->lockForUpdate()->count() + 1);
            $tender = Tender::create([
                ...$data,
                'title' => $data['title'] ?: 'Request for quotation — '.$purchaseRequisition->requisition_number,
                'contact_email' => $data['contact_email'] ?: $request->user()->email,
                'purchase_requisition_id' => $purchaseRequisition->id,
                'supplier_category_id' => $purchaseRequisition->supplier_category_id,
                'tender_number' => $number,
                'tender_type' => 'rfq',
                'visibility' => 'public',
                'created_by' => $request->user()->id,
                'status' => Tender::STATUS_PENDING_PUBLICATION,
            ]);
            $tender->items()->createMany($purchaseRequisition->items->map(fn ($item) => [
                'purchase_requisition_item_id' => $item->id,
                'item_name' => $item->item_name,
                'specification' => $item->specification,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
            ])->all());
            ActivityLog::record($request->user(), 'tender.created_for_other_suppliers', $tender, [], [
                'status' => Tender::STATUS_PENDING_PUBLICATION,
                'purchase_requisition_id' => $purchaseRequisition->id,
            ]);

            return $tender;
        });

        return response()->json([
            'message' => 'Public RFQ created and sent to the GM for publication approval.',
            'data' => $tender->load(['items', 'category']),
        ], 201);
    }

    public function show(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        abort_unless($this->entityAccess->canAccess($request->user(), $tender->requisition->business_entity_id), 403);

        return response()->json(['data' => $tender->load(['items', 'category', 'requisition:id,requisition_number,status,required_date,purpose,business_entity_id', 'invitedSuppliers:id,name,code,portal_status', 'winningResponse.supplier:id,name,code,email,phone'])]);
    }

    public function update(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        abort_unless($tender->status === Tender::STATUS_DRAFT, 422, 'Only draft tenders can be edited.');
        $tender->update($this->validateTender($request, $tender));

        return response()->json(['message' => 'Tender updated.', 'data' => $tender->fresh()]);
    }

    public function submitPublication(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        abort_unless($tender->status === Tender::STATUS_DRAFT, 422);
        $tender->update(['status' => Tender::STATUS_PENDING_PUBLICATION]);

        return response()->json(['message' => 'Tender submitted for publication.', 'data' => $tender]);
    }

    public function publish(Request $request, Tender $tender): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate(['comments' => ['required', 'string', 'max:2000']]);
        abort_unless($tender->status === Tender::STATUS_PENDING_PUBLICATION, 422, 'Tender is not awaiting publication.');
        abort_if($tender->submission_deadline->isPast(), 422, 'Deadline must be in the future.');
        $tender->update(['status' => Tender::STATUS_PUBLISHED, 'published_by' => $request->user()->id, 'published_at' => now(), 'publication_at' => $tender->publication_at ?? now()]);
        ActivityLog::record($request->user(), 'tender.published', $tender, ['status' => Tender::STATUS_PENDING_PUBLICATION], ['status' => Tender::STATUS_PUBLISHED, 'comments' => $data['comments']]);

        return response()->json(['message' => 'Tender published.', 'data' => $tender->fresh()]);
    }

    public function invite(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        $data = $request->validate(['supplier_ids' => ['required', 'array', 'min:1'], 'supplier_ids.*' => ['integer', 'exists:suppliers,id']]);
        $suppliers = Supplier::with(['documents', 'categories'])->whereIn('id', $data['supplier_ids'])->get();
        $eligible = $suppliers->filter(fn (Supplier $supplier) => $supplier->categories->contains('id', $tender->supplier_category_id) && $this->compliance->canParticipate($supplier))->pluck('id');
        abort_unless($eligible->count() === count($data['supplier_ids']), 422, 'Every invited supplier must be approved, active, category-eligible, and compliant.');
        $tender->invitedSuppliers()->syncWithoutDetaching($eligible->mapWithKeys(fn ($id) => [$id => ['invited_at' => now(), 'invited_by' => $request->user()->id]])->all());

        return response()->json(['message' => 'Eligible suppliers invited.']);
    }

    public function close(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        abort_unless($tender->status === Tender::STATUS_PUBLISHED, 422, 'Only a published tender can be closed.');
        abort_if($tender->submission_deadline->isFuture(), 422, 'Bidding cannot be closed before the submission deadline.');
        $tender->update(['status' => Tender::STATUS_CLOSED, 'closed_by' => $request->user()->id, 'closed_at' => now()]);
        ActivityLog::record($request->user(), 'tender.bidding_closed', $tender, ['status' => Tender::STATUS_PUBLISHED], ['status' => Tender::STATUS_CLOSED]);

        return response()->json(['message' => 'Bidding closed. Supplier responses are now available for evaluation.', 'data' => $tender->fresh()]);
    }

    public function cancel(Request $request, Tender $tender): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        abort_if(in_array($tender->status, [Tender::STATUS_AWARDED, Tender::STATUS_CANCELLED], true), 422);
        $old = $tender->status;
        $tender->update(['status' => Tender::STATUS_CANCELLED, 'cancellation_reason' => $data['reason']]);
        ActivityLog::record($request->user(), 'tender.cancelled', $tender, ['status' => $old], ['status' => Tender::STATUS_CANCELLED, 'reason' => $data['reason']]);

        return response()->json(['message' => 'Tender cancelled.']);
    }

    public function responses(Request $request, Tender $tender): JsonResponse
    {
        $this->internal($request);
        abort_if($tender->status === Tender::STATUS_PUBLISHED && $tender->submission_deadline->isFuture(), 403, 'Responses remain sealed until the deadline.');

        return response()->json(['data' => $tender->responses()->with([
            'supplier' => fn ($query) => $query->with(['currentPerformance', 'performanceIncidents' => fn ($incident) => $incident->whereNull('resolved_at')->whereIn('severity', ['high', 'critical'])]),
            'items.tenderItem', 'documents', 'quotation',
        ])->paginate(15)]);
    }

    public function downloadResponseDocument(Request $request, TenderResponseDocument $document)
    {
        $this->internal($request);
        abort_if($document->response->tender->status === Tender::STATUS_PUBLISHED && $document->response->tender->submission_deadline->isFuture(), 403, 'Responses remain sealed until the deadline.');

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    public function compliance(Request $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->internal($request);
        abort_if($tenderResponse->tender->status === Tender::STATUS_PUBLISHED && $tenderResponse->tender->submission_deadline->isFuture(), 403, 'Responses remain sealed until the deadline.');
        abort_unless(in_array($tenderResponse->tender->status, [Tender::STATUS_CLOSED, Tender::STATUS_EVALUATION], true), 422, 'Close bidding before reviewing supplier responses.');
        $data = $request->validate(['decision' => ['required', 'in:compliant,non_compliant'], 'comments' => ['required', 'string', 'max:2000']]);
        abort_unless($tenderResponse->status === 'submitted', 422, 'Only submitted responses can be reviewed.');
        DB::transaction(function () use ($request, $tenderResponse, $data) {
            $quotationId = null;
            if ($data['decision'] === 'compliant') {
                $tenderResponse->load(['tender.requisition', 'items.tenderItem']);
                $quotation = SupplierQuotation::create([
                    'purchase_requisition_id' => $tenderResponse->tender->purchase_requisition_id,
                    'supplier_id' => $tenderResponse->supplier_id, 'prepared_by' => $request->user()->id,
                    'quotation_number' => $tenderResponse->quotation_number, 'valid_until' => $tenderResponse->valid_until,
                    'total_amount' => $tenderResponse->total_amount, 'status' => SupplierQuotation::STATUS_ACTIVE,
                    'notes' => 'Imported from '.$tenderResponse->receipt_number, 'submitted_at' => $tenderResponse->submitted_at,
                ]);
                $quotation->items()->createMany($tenderResponse->items->map(fn ($item) => [
                    'item_name' => $item->tenderItem->item_name, 'specification' => $item->offered_specification ?: $item->tenderItem->specification,
                    'quantity' => $item->tenderItem->quantity, 'unit' => $item->tenderItem->unit,
                    'unit_price' => $item->unit_price, 'total_price' => $item->line_total, 'notes' => $item->brand_make,
                ])->all());
                $quotationId = $quotation->id;
            }
            $tenderResponse->update(['status' => $data['decision'], 'compliance_comments' => $data['comments'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'supplier_quotation_id' => $quotationId]);
            ActivityLog::record($request->user(), 'tender_response.'.$data['decision'], $tenderResponse, ['status' => 'submitted'], ['status' => $data['decision'], 'comments' => $data['comments']]);
        });

        return response()->json(['message' => 'Compliance decision recorded.', 'data' => $tenderResponse->fresh()]);
    }

    public function award(Request $request, Tender $tender, TenderResponse $tenderResponse): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'ceo']), 403);
        abort_unless($tenderResponse->tender_id === $tender->id, 404);
        abort_unless(in_array($tender->status, [Tender::STATUS_CLOSED, Tender::STATUS_EVALUATION], true), 422, 'Close bidding before selecting a winner.');
        abort_if($tender->submission_deadline->isFuture(), 422, 'A winner cannot be selected before the submission deadline.');
        abort_unless($tenderResponse->status === 'compliant' && $tenderResponse->supplier_quotation_id, 422, 'Only a compliant evaluated bid can be selected.');
        $data = $request->validate(['comments' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($request, $tender, $tenderResponse, $data) {
            $tender->responses()->where('id', '!=', $tenderResponse->id)->where('status', '!=', 'draft')->update(['award_status' => 'unsuccessful']);
            $tenderResponse->update(['award_status' => 'winner']);
            $tender->update([
                'status' => Tender::STATUS_AWARDED,
                'winning_tender_response_id' => $tenderResponse->id,
                'awarded_by' => $request->user()->id,
                'awarded_at' => now(),
                'award_comments' => $data['comments'],
            ]);
            $tender->requisition()->update(['status' => PurchaseRequisition::STATUS_QUOTATIONS_READY]);
            ActivityLog::record($request->user(), 'tender.awarded', $tender, ['status' => Tender::STATUS_CLOSED], [
                'status' => Tender::STATUS_AWARDED,
                'winning_tender_response_id' => $tenderResponse->id,
                'supplier_id' => $tenderResponse->supplier_id,
                'amount' => $tenderResponse->total_amount,
                'comments' => $data['comments'],
            ]);
        });

        $tenderResponse->loadMissing(['supplier.user', 'tender']);
        if ($tenderResponse->supplier?->user) {
            $tenderResponse->supplier->user->notify(new TenderAwardedNotification($tenderResponse));
            $tenderResponse->update(['award_notified_at' => now()]);
        }

        return response()->json([
            'message' => 'Winning bid selected, the supplier was notified, and the requisition was returned for supplier selection review.',
            'data' => $tender->fresh(['winningResponse.supplier', 'requisition']),
        ]);
    }

    private function internal(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'gm', 'ceo']), 403);
    }

    private function validateTender(Request $request, ?Tender $tender = null): array
    {
        return $request->validate([
            'purchase_requisition_id' => [$tender ? 'sometimes' : 'required', 'integer', 'exists:purchase_requisitions,id'],
            'supplier_category_id' => ['required', 'integer', 'exists:supplier_categories,id'], 'title' => ['required', 'string', 'max:255'],
            'public_summary' => ['required', 'string', 'max:5000'], 'tender_type' => ['required', 'in:rfq,open_tender,restricted_rfq'],
            'visibility' => ['required', 'in:public,invited_only'], 'publication_at' => ['nullable', 'date'],
            'submission_deadline' => ['required', 'date', 'after:now'], 'expected_delivery_date' => ['nullable', 'date', 'after:today'],
            'delivery_location' => ['nullable', 'string', 'max:255'], 'contact_email' => ['required', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'], 'eligibility_requirements' => ['nullable', 'string', 'max:10000'],
            'submission_instructions' => ['nullable', 'string', 'max:10000'], 'terms_and_conditions' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
