<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends CeoAwareFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'procurement_officer']);
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'legal_name' => ['sometimes', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers')->ignore($supplierId)],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'supplier_type' => ['sometimes', 'in:limited_company,business_name,partnership,sole_proprietor,ngo,government_entity,other'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'brela_registration_number' => ['nullable', 'string', 'max:100'],
            'incorporation_or_compliance_number' => ['nullable', 'string', 'max:100'],
            'business_license_number' => ['nullable', 'string', 'max:100'],
            'business_license_issuing_authority' => ['nullable', 'string', 'max:255'],
            'business_license_expiry_date' => ['nullable', 'date'],
            'tin_number' => ['sometimes', 'string', 'max:100'],
            'vat_registered' => ['boolean'],
            'vat_registration_number' => ['nullable', 'string', 'max:100'],
            'tax_clearance_number' => ['nullable', 'string', 'max:100'],
            'tax_clearance_expiry_date' => ['nullable', 'date'],
            'physical_office_address' => ['sometimes', 'string'],
            'building_plot_street' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'region' => ['sometimes', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_address' => ['nullable', 'string'],
            'primary_contact_name' => ['sometimes', 'string', 'max:255'],
            'primary_contact_position' => ['nullable', 'string', 'max:255'],
            'primary_contact_phone' => ['sometimes', 'string', 'max:50'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'alternate_contact_name' => ['nullable', 'string', 'max:255'],
            'alternate_contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'products_services' => ['sometimes', 'string', 'max:5000'],
            'manufacturer_or_distributor_status' => ['nullable', 'string', 'max:100'],
            'years_in_operation' => ['nullable', 'integer', 'min:0', 'max:500'],
            'delivery_coverage_areas' => ['nullable', 'string', 'max:5000'],
            'quality_management_notes' => ['nullable', 'string', 'max:5000'],
            'regulated_supplier' => ['boolean'],
            'category_ids' => ['sometimes', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:supplier_categories,id'],
            'is_active' => ['boolean'],
        ];
    }
}
