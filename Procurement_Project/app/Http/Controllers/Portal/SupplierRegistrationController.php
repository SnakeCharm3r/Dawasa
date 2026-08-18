<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class SupplierRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'legal_name' => ['required', 'string', 'max:255'], 'trading_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:suppliers,registration_number'],
            'brela_registration_number' => ['nullable', 'string', 'max:100'],
            'incorporation_or_compliance_number' => ['nullable', 'string', 'max:100'],
            'business_license_number' => ['nullable', 'string', 'max:100'],
            'business_license_issuing_authority' => ['nullable', 'string', 'max:255'],
            'business_license_expiry_date' => ['nullable', 'date'],
            'tin_number' => ['required', 'string', 'max:100', 'unique:suppliers,tax_number'],
            'vat_registered' => ['boolean'], 'vat_registration_number' => ['nullable', 'required_if:vat_registered,true', 'string', 'max:100'],
            'tax_clearance_number' => ['nullable', 'string', 'max:100'], 'tax_clearance_expiry_date' => ['nullable', 'date'],
            'supplier_type' => ['required', 'in:limited_company,business_name,partnership,sole_proprietor,ngo,government_entity,other,company,sole_trader,manufacturer,distributor,consultant'],
            'physical_office_address' => ['required_without:address', 'string'], 'address' => ['required_without:physical_office_address', 'string'], 'building_plot_street' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:100'], 'district' => ['nullable', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'], 'country' => ['required', 'string', 'max:100'],
            'postal_address' => ['nullable', 'string'], 'website' => ['nullable', 'url', 'max:255'],
            'primary_contact_name' => ['required_without:contact_name', 'string', 'max:255'], 'contact_name' => ['required_without:primary_contact_name', 'string', 'max:255'],
            'primary_contact_position' => ['nullable', 'string', 'max:255'], 'contact_position' => ['nullable', 'string', 'max:255'],
            'primary_contact_phone' => ['required_without:phone', 'string', 'max:50'], 'phone' => ['required_without:primary_contact_phone', 'string', 'max:50'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'alternate_contact_name' => ['nullable', 'string', 'max:255'], 'alternate_contact_phone' => ['nullable', 'string', 'max:50'], 'alternate_phone' => ['nullable', 'string', 'max:50'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:supplier_categories,id'], 'products_services' => ['required', 'string', 'max:5000'],
            'manufacturer_or_distributor_status' => ['nullable', 'string', 'max:100'], 'years_in_operation' => ['nullable', 'integer', 'min:0', 'max:500'],
            'delivery_coverage_areas' => ['nullable', 'string', 'max:5000'], 'quality_management_notes' => ['nullable', 'string', 'max:5000'],
            'regulated_supplier' => ['boolean'],
            'declaration_accurate' => ['accepted'], 'agree_terms' => ['accepted'], 'agree_privacy' => ['accepted'],
        ]);

        $supplier = DB::transaction(function () use ($data) {
            $data['primary_contact_name'] = $data['primary_contact_name'] ?? $data['contact_name'];
            $data['primary_contact_position'] = $data['primary_contact_position'] ?? $data['contact_position'] ?? null;
            $data['primary_contact_phone'] = $data['primary_contact_phone'] ?? $data['phone'];
            $data['primary_contact_email'] = $data['primary_contact_email'] ?? $data['email'];
            $data['alternate_contact_phone'] = $data['alternate_contact_phone'] ?? $data['alternate_phone'] ?? null;
            $data['physical_office_address'] = $data['physical_office_address'] ?? $data['address'];
            $data['supplier_type'] = match ($data['supplier_type']) {
                'company', 'manufacturer', 'distributor', 'consultant' => 'limited_company',
                'sole_trader' => 'sole_proprietor',
                default => $data['supplier_type'],
            };
            $nameParts = preg_split('/\s+/', trim($data['primary_contact_name']), 2);
            $user = User::create([
                'name' => $data['primary_contact_name'], 'first_name' => $nameParts[0], 'last_name' => $nameParts[1] ?? '-', 'email' => $data['email'],
                'phone' => $data['primary_contact_phone'], 'job_title' => $data['primary_contact_position'] ?? 'Supplier contact', 'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->markEmailAsVerified();
            $role = Role::findOrCreate('supplier', 'web');
            $user->assignRole($role);

            $supplier = Supplier::create([
                'user_id' => $user->id, 'name' => $data['legal_name'], 'legal_name' => $data['legal_name'], 'trading_name' => $data['trading_name'] ?? null,
                'code' => 'SUP-PENDING-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                'application_reference' => $this->nextReference(), 'contact_person' => $data['primary_contact_name'], 'primary_contact_name' => $data['primary_contact_name'],
                'contact_position' => $data['primary_contact_position'] ?? null, 'primary_contact_position' => $data['primary_contact_position'] ?? null,
                'email' => $data['primary_contact_email'], 'primary_contact_email' => $data['primary_contact_email'],
                'phone' => $data['primary_contact_phone'], 'primary_contact_phone' => $data['primary_contact_phone'],
                'alternate_phone' => $data['alternate_contact_phone'] ?? null, 'alternate_contact_name' => $data['alternate_contact_name'] ?? null,
                'alternate_contact_phone' => $data['alternate_contact_phone'] ?? null,
                'address' => $data['physical_office_address'], 'physical_office_address' => $data['physical_office_address'],
                'building_plot_street' => $data['building_plot_street'] ?? null, 'ward' => $data['ward'] ?? null, 'district' => $data['district'] ?? null,
                'region' => $data['region'], 'country' => $data['country'], 'website' => $data['website'] ?? null,
                'postal_address' => $data['postal_address'] ?? null,
                'tax_number' => $data['tin_number'], 'tin_number' => $data['tin_number'],
                'vat_registered' => $data['vat_registered'] ?? false, 'vat_number' => $data['vat_registration_number'] ?? null, 'vat_registration_number' => $data['vat_registration_number'] ?? null,
                'tax_clearance_number' => $data['tax_clearance_number'] ?? null, 'tax_clearance_expiry_date' => $data['tax_clearance_expiry_date'] ?? null,
                'registration_number' => $data['registration_number'] ?? null, 'supplier_type' => $data['supplier_type'],
                'brela_registration_number' => $data['brela_registration_number'] ?? null, 'incorporation_or_compliance_number' => $data['incorporation_or_compliance_number'] ?? null,
                'business_license_number' => $data['business_license_number'] ?? null, 'business_license_issuing_authority' => $data['business_license_issuing_authority'] ?? null,
                'business_license_expiry_date' => $data['business_license_expiry_date'] ?? null,
                'products_services' => $data['products_services'], 'manufacturer_or_distributor_status' => $data['manufacturer_or_distributor_status'] ?? null,
                'years_in_operation' => $data['years_in_operation'] ?? null, 'delivery_coverage_areas' => $data['delivery_coverage_areas'] ?? null,
                'quality_management_notes' => $data['quality_management_notes'] ?? null, 'regulated_supplier' => $data['regulated_supplier'] ?? false,
                'portal_status' => 'pending_verification', 'submitted_at' => now(), 'is_active' => false,
            ]);
            $supplier->categories()->sync($data['category_ids']);
            ActivityLog::record($user, 'supplier.application_submitted', $supplier, [], ['portal_status' => 'pending_verification']);

            return $supplier;
        });

        Auth::login($supplier->user);
        $request->session()->regenerate();

        return response()->json(['message' => 'Supplier account created. You now have direct portal access.', 'data' => [
            'application_reference' => $supplier->application_reference, 'status' => $supplier->portal_status,
        ]], 201);
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $sequence = Supplier::withTrashed()->whereYear('created_at', $year)->lockForUpdate()->count() + 1;

        return sprintf('SUP-APP-%d-%05d', $year, $sequence);
    }
}
