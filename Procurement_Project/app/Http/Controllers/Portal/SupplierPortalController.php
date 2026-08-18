<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupplierDocument;
use App\Models\Tender;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierPortalController extends Controller
{
    public function __construct(private readonly SupplierComplianceService $compliance) {}

    public function dashboard(Request $request): JsonResponse
    {
        $supplier = $this->supplier($request)->load(['categories:id,name,code', 'documents']);
        $assessment = $this->compliance->assess($supplier);

        return response()->json(['data' => [
            'supplier' => $this->portalSafe($supplier), 'profile_completion' => $this->completion($supplier),
            'compliance' => collect($assessment)->only([
                'status', 'required_count', 'valid_count', 'score', 'missing_documents',
                'expired_documents', 'rejected_documents', 'expiring_documents',
            ]),
            'response_counts' => $supplier->tenderResponses()->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status'),
            'expiring_document_count' => $supplier->documents->filter(fn ($document) => $document->expires_at && $document->expires_at->between(now(), now()->addDays(30)))->count(),
        ]]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->portalSafe($this->supplier($request)->load(['categories:id,name,code', 'documents']))]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $supplier = $this->supplier($request);
        abort_unless(in_array($supplier->portal_status, ['draft', 'pending_submission', 'pending_verification', 'requires_correction', 'approved'], true), 422, 'This supplier profile cannot be edited at its current status.');
        $data = $request->validate([
            'trading_name' => ['nullable', 'string', 'max:255'], 'physical_office_address' => ['required', 'string'],
            'building_plot_street' => ['nullable', 'string'], 'ward' => ['nullable', 'string'], 'district' => ['nullable', 'string'], 'postal_address' => ['nullable', 'string'],
            'region' => ['required', 'string', 'max:100'], 'country' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url'], 'primary_contact_name' => ['required', 'string'], 'primary_contact_position' => ['nullable', 'string'],
            'primary_contact_phone' => ['required', 'string', 'max:50'], 'primary_contact_email' => ['required', 'email'],
            'alternate_contact_name' => ['nullable', 'string'], 'alternate_contact_phone' => ['nullable', 'string', 'max:50'],
            'products_services' => ['required', 'string'], 'manufacturer_or_distributor_status' => ['nullable', 'string'],
            'years_in_operation' => ['nullable', 'integer', 'min:0'], 'delivery_coverage_areas' => ['nullable', 'string'], 'quality_management_notes' => ['nullable', 'string'],
            'regulated_supplier' => ['boolean'],
            'category_ids' => ['required', 'array', 'min:1'], 'category_ids.*' => ['integer', 'exists:supplier_categories,id'],
        ]);
        $profile = collect($data)->except('category_ids')->all();
        $profile += [
            'address' => $data['physical_office_address'], 'contact_person' => $data['primary_contact_name'],
            'contact_position' => $data['primary_contact_position'] ?? null, 'phone' => $data['primary_contact_phone'],
            'email' => $data['primary_contact_email'], 'alternate_phone' => $data['alternate_contact_phone'] ?? null,
        ];
        if ($supplier->portal_status === 'approved') {
            $profile += ['portal_status' => 'pending_verification', 'is_active' => false, 'submitted_at' => now()];
        }
        $supplier->update($profile);
        $supplier->categories()->sync($data['category_ids']);
        ActivityLog::record($request->user(), 'supplier.profile_updated_by_supplier', $supplier, [], ['portal_status' => $supplier->portal_status]);
        $this->compliance->assess($supplier);

        return response()->json(['message' => 'Profile updated and submitted for compliance review.', 'data' => $this->portalSafe($supplier->fresh('categories'))]);
    }

    public function documents(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->supplier($request)->documents()->latest()->paginate(20)]);
    }

    public function storeDocument(Request $request): JsonResponse
    {
        $supplier = $this->supplier($request);
        $data = $request->validate([
            'document_type' => ['required', 'in:certificate_of_incorporation_or_business_registration,brela_compliance_document,business_license,tin_certificate,vat_certificate,tax_clearance_certificate,proof_of_physical_office,director_or_owner_identification,bank_confirmation,product_catalogue,manufacturer_authorisation,quality_certificate,tmda_or_regulatory_license,import_or_wholesale_permit,contract,other'],
            'document_number' => ['nullable', 'string', 'max:100'], 'issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:today'], 'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $data['file'];
        $path = $file->store('supplier-documents/'.$supplier->id, 'local');
        $document = $supplier->documents()->create([
            'document_type' => $data['document_type'], 'document_number' => $data['document_number'] ?? null,
            'issue_date' => $data['issue_date'] ?? null, 'expiry_date' => $data['expiry_date'] ?? null, 'expires_at' => $data['expiry_date'] ?? null,
            'original_name' => $file->getClientOriginalName(), 'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path, 'file_path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
            'status' => 'pending_verification', 'verification_status' => 'pending',
        ]);
        $this->compliance->assess($supplier);

        return response()->json(['message' => 'Document uploaded securely.', 'data' => $document], 201);
    }

    public function downloadDocument(Request $request, SupplierDocument $document)
    {
        abort_unless($document->supplier_id === $this->supplier($request)->id, 403);

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    public function tenders(Request $request): JsonResponse
    {
        $supplier = $this->supplier($request);
        $query = Tender::query()->with('category:id,name,code')->where('status', Tender::STATUS_PUBLISHED)
            ->where('submission_deadline', '>', now())->where(function ($q) use ($supplier) {
                $q->where('visibility', 'public')
                    ->orWhereHas('invitedSuppliers', fn ($invited) => $invited->where('suppliers.id', $supplier->id));
            });
        $page = $query->orderBy('submission_deadline')->paginate(15);
        $page->through(fn (Tender $tender) => [
            'id' => $tender->id, 'tender_number' => $tender->tender_number, 'title' => $tender->title,
            'public_summary' => $tender->public_summary, 'tender_type' => $tender->tender_type,
            'status' => $tender->status, 'submission_deadline' => $tender->submission_deadline,
            'category' => $tender->category,
        ]);

        return response()->json(['data' => $page]);
    }

    public function showTender(Request $request, Tender $tender): JsonResponse
    {
        $supplier = $this->supplier($request);
        $invited = $tender->invitedSuppliers()->whereKey($supplier->id)->exists();
        abort_unless($tender->status === Tender::STATUS_PUBLISHED && ($tender->visibility === 'public' || $invited), 403);
        $tender->load('items:id,tender_id,item_name,specification,quantity,unit');

        return response()->json(['data' => [
            'id' => $tender->id, 'tender_number' => $tender->tender_number, 'title' => $tender->title,
            'public_summary' => $tender->public_summary, 'tender_type' => $tender->tender_type,
            'status' => $tender->status, 'submission_deadline' => $tender->submission_deadline,
            'expected_delivery_date' => $tender->expected_delivery_date, 'delivery_location' => $tender->delivery_location,
            'eligibility_requirements' => $tender->eligibility_requirements, 'submission_instructions' => $tender->submission_instructions,
            'terms_and_conditions' => $tender->terms_and_conditions, 'items' => $tender->items,
        ]]);
    }

    private function supplier(Request $request)
    {
        abort_unless($request->user()->hasRole('supplier'), 403);

        return $request->user()->supplier()->firstOrFail();
    }

    private function completion($supplier): int
    {
        $fields = ['name', 'registration_number', 'tax_number', 'address', 'region', 'contact_person', 'phone', 'products_services'];
        $done = collect($fields)->filter(fn ($field) => filled($supplier->{$field}))->count();

        return (int) round(($done / count($fields)) * 80 + min($supplier->documents->count(), 4) * 5);
    }

    private function portalSafe($supplier)
    {
        return $supplier->makeHidden(['award_eligibility', 'restriction_reason', 'restriction_expires_at', 'is_preferred', 'performanceEvaluations', 'performanceIncidents', 'performanceOverrides']);
    }
}
