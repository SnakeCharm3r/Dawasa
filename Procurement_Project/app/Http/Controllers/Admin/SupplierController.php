<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Services\SupplierComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierComplianceService $compliance) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::with(['categories:id,name,code', 'currentPerformance'])->withCount(['performanceIncidents as open_incidents_count' => fn ($incident) => $incident->whereNull('resolved_at')]);

        if ($request->has('search')) {
            $query->where(fn ($search) => $search->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('code', 'like', '%'.$request->input('search').'%'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->filled('status')) {
            $query->where('portal_status', $request->input('status'));
        }

        $suppliers = $query->orderBy('name')->paginate($request->input('per_page', 15));

        $suppliers->getCollection()->each(fn (Supplier $supplier) => $this->compliance->assess($supplier));

        return response()->json(['data' => $suppliers]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $data = $this->normalise($request->validated());
        $categories = $data['category_ids'] ?? [];
        unset($data['category_ids']);
        $supplier = Supplier::create([...$data, 'portal_status' => 'pending_verification', 'is_active' => false, 'submitted_at' => now()]);
        $supplier->categories()->sync($categories);
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.manually_created', $supplier, [], ['portal_status' => $supplier->portal_status]);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => $supplier->load('categories'),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $documents = $supplier->documents();
        if (! request()->user()->hasAnyRole(['super_admin', 'accountant', 'gm', 'ceo', 'auditor'])) {
            $documents->where('document_type', '<>', 'bank_confirmation');
        }
        $supplier->load(['quotations', 'categories:id,name,code', 'currentPerformance', 'performanceIncidents' => fn ($query) => $query->latest('occurred_at')]);
        $supplier->setRelation('documents', $documents->latest()->get());
        $supplier->setAttribute('compliance_assessment', $this->compliance->assess($supplier));

        return response()->json(['data' => $supplier]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $data = $this->normalise($request->validated());
        $categories = $data['category_ids'] ?? null;
        unset($data['category_ids']);
        $supplier->update($data);
        if ($categories !== null) {
            $supplier->categories()->sync($categories);
        }
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.profile_updated', $supplier, [], array_keys($data));

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

    public function activate(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);
        abort_unless($request->user()->hasAnyRole(['super_admin', 'procurement_officer', 'gm', 'ceo']), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $assessment = $this->compliance->assess($supplier, false);
        $old = $supplier->only(['is_active', 'portal_status']);
        $supplier->update(['is_active' => true, 'portal_status' => 'approved', 'review_comments' => $data['reason'], 'status_changed_by' => $request->user()->id, 'status_changed_at' => now()]);
        $override = $this->compliance->hasKycGaps($assessment)
            ? $this->compliance->grantKycEligibilityOverride($supplier, $request->user(), $data['reason'])
            : null;
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.reactivated', $supplier, $old, [
            'is_active' => true,
            'portal_status' => 'approved',
            'award_eligibility' => $supplier->award_eligibility,
            'kyc_override_expires_at' => $override?->expires_at,
            'reason' => $data['reason'],
        ]);

        return response()->json([
            'message' => $override
                ? 'Supplier verified and activated with a 90-day incomplete-KYC eligibility override.'
                : 'Supplier verified and activated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function deactivate(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);
        abort_unless($request->user()->hasAnyRole(['super_admin', 'gm', 'ceo']), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $old = $supplier->only(['is_active', 'portal_status']);
        $supplier->update(['is_active' => false, 'portal_status' => 'suspended', 'review_comments' => $data['reason'], 'status_changed_by' => $request->user()->id, 'status_changed_at' => now()]);
        $this->compliance->assess($supplier);
        ActivityLog::record($request->user(), 'supplier.suspended', $supplier, $old, ['is_active' => false, 'portal_status' => 'suspended', 'reason' => $data['reason']]);

        return response()->json([
            'message' => 'Supplier deactivated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    private function normalise(array $data): array
    {
        $data['name'] = $data['legal_name'] ?? $data['name'] ?? null;
        $data['legal_name'] = $data['legal_name'] ?? $data['name'];
        $data['tax_number'] = $data['tin_number'] ?? $data['tax_number'] ?? null;
        $data['tin_number'] = $data['tin_number'] ?? $data['tax_number'];
        $data['vat_number'] = $data['vat_registration_number'] ?? $data['vat_number'] ?? null;
        $data['vat_registration_number'] = $data['vat_registration_number'] ?? $data['vat_number'];
        $data['address'] = $data['physical_office_address'] ?? $data['address'] ?? null;
        $data['physical_office_address'] = $data['physical_office_address'] ?? $data['address'];
        $data['contact_person'] = $data['primary_contact_name'] ?? $data['contact_person'] ?? null;
        $data['primary_contact_name'] = $data['primary_contact_name'] ?? $data['contact_person'];
        $data['phone'] = $data['primary_contact_phone'] ?? $data['phone'] ?? null;
        $data['primary_contact_phone'] = $data['primary_contact_phone'] ?? $data['phone'];
        $data['email'] = $data['primary_contact_email'] ?? $data['email'] ?? null;
        $data['primary_contact_email'] = $data['primary_contact_email'] ?? $data['email'];
        $data['contact_position'] = $data['primary_contact_position'] ?? $data['contact_position'] ?? null;
        $data['alternate_phone'] = $data['alternate_contact_phone'] ?? $data['alternate_phone'] ?? null;
        $data['country'] ??= 'Tanzania';

        return $data;
    }
}
