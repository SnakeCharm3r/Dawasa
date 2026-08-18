<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierVerificationController extends Controller
{
    public function __construct(private readonly SupplierComplianceService $compliance) {}

    public function applications(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'accountant', 'gm', 'ceo', 'auditor']), 403);
        $query = Supplier::with(['categories:id,name,code', 'documents', 'currentPerformance'])->withCount(['documents', 'documents as verified_documents_count' => fn ($q) => $q->where(fn ($document) => $document->where('verification_status', 'verified')->orWhere('status', 'verified'))]);
        $query->when($request->filled('status'), fn ($q) => $q->where('portal_status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->string('search').'%')->orWhere('application_reference', 'like', '%'.$request->string('search').'%')->orWhere('tax_number', 'like', '%'.$request->string('search').'%')));
        $suppliers = $query->latest()->paginate(min($request->integer('per_page', 15), 50));
        $suppliers->getCollection()->each(fn (Supplier $supplier) => $supplier->setAttribute('compliance_assessment', $this->compliance->assess($supplier)));

        return response()->json(['data' => $suppliers]);
    }

    public function decision(Request $request, Supplier $supplier): JsonResponse
    {
        $this->reviewer($request);
        $data = $request->validate(['decision' => ['required', 'in:approved,requires_correction,rejected,suspended,reactivated'], 'comments' => ['required', 'string', 'max:2000']]);
        $newStatus = $data['decision'] === 'reactivated' ? 'approved' : $data['decision'];
        $assessment = $this->compliance->assess($supplier, false);
        $old = $supplier->portal_status;
        $supplier->update([
            'portal_status' => $newStatus, 'review_comments' => $data['comments'],
            'is_active' => $newStatus === 'approved', 'verified_by' => $newStatus === 'approved' ? $request->user()->id : $supplier->verified_by,
            'verified_at' => $newStatus === 'approved' ? now() : $supplier->verified_at,
            'code' => $newStatus === 'approved' && str_starts_with($supplier->code, 'SUP-PENDING-') ? 'SUP-'.str_pad((string) $supplier->id, 6, '0', STR_PAD_LEFT) : $supplier->code,
            'status_changed_by' => $request->user()->id,
            'status_changed_at' => now(),
        ]);
        $override = in_array($data['decision'], ['approved', 'reactivated'], true) && $this->compliance->hasKycGaps($assessment)
            ? $this->compliance->grantKycEligibilityOverride($supplier, $request->user(), $data['comments'])
            : null;
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.'.$data['decision'], $supplier, ['portal_status' => $old], [
            'portal_status' => $newStatus,
            'award_eligibility' => $supplier->award_eligibility,
            'kyc_override_expires_at' => $override?->expires_at,
            'comments' => $data['comments'],
        ]);

        return response()->json([
            'message' => $override ? 'Supplier verified with a 90-day incomplete-KYC eligibility override.' : 'Supplier decision recorded.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function documentDecision(Request $request, SupplierDocument $document): JsonResponse
    {
        $this->reviewer($request);
        $data = $request->validate(['decision' => ['required', 'in:verified,rejected'], 'comments' => ['required', 'string', 'max:2000']]);
        $old = $document->verification_status ?: $document->status;
        $document->update([
            'status' => $data['decision'],
            'verification_status' => $data['decision'],
            'review_comments' => $data['comments'],
            'verification_notes' => $data['comments'],
            'reviewed_by' => $request->user()->id,
            'verified_by' => $request->user()->id,
            'reviewed_at' => now(),
            'verified_at' => now(),
        ]);
        $this->compliance->assess($document->supplier);
        ActivityLog::record($request->user(), 'supplier_document.'.$data['decision'], $document, ['status' => $old], ['status' => $data['decision'], 'comments' => $data['comments']]);

        return response()->json(['message' => 'Document review recorded.', 'data' => $document->fresh()]);
    }

    public function preferred(Request $request, Supplier $supplier): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate(['preferred' => ['required', 'boolean'], 'comments' => ['required', 'string', 'max:2000']]);
        if ($data['preferred']) {
            $assessment = $this->compliance->assess($supplier);
            abort_unless($assessment['award_eligibility'] === 'eligible', 422, 'Only a fully compliant supplier can be marked preferred.');
            abort_unless($supplier->currentPerformance && $supplier->currentPerformance->grade === 'A', 422, 'Preferred status requires a current Grade A performance evaluation.');
        }
        $old = $supplier->is_preferred;
        $supplier->update(['is_preferred' => $data['preferred'], 'status_changed_by' => $request->user()->id, 'status_changed_at' => now()]);
        ActivityLog::record($request->user(), 'supplier.preferred_status_changed', $supplier, ['is_preferred' => $old], ['is_preferred' => $data['preferred'], 'comments' => $data['comments']]);

        return response()->json(['message' => 'Supplier preferred status recorded.', 'data' => $supplier->fresh()]);
    }

    public function download(Request $request, SupplierDocument $document)
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'gm', 'accountant', 'ceo', 'auditor']), 403);
        if ($document->document_type === 'bank_confirmation') {
            abort_unless($request->user()->hasAnyRole(['super_admin', 'accountant', 'ceo']), 403);
        }

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    private function reviewer(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'gm', 'ceo']), 403);
    }
}
