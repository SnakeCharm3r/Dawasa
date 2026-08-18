<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'user_id',
        'code',
        'application_reference',
        'trading_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_number',
        'vat_number',
        'registration_number',
        'brela_registration_number',
        'incorporation_or_compliance_number',
        'business_license_number',
        'business_license_issuing_authority',
        'business_license_expiry_date',
        'tin_number',
        'vat_registered',
        'vat_registration_number',
        'tax_clearance_number',
        'tax_clearance_expiry_date',
        'supplier_type',
        'region',
        'country',
        'physical_office_address',
        'building_plot_street',
        'ward',
        'district',
        'postal_address',
        'website',
        'contact_position',
        'alternate_phone',
        'primary_contact_name',
        'primary_contact_position',
        'primary_contact_phone',
        'primary_contact_email',
        'alternate_contact_name',
        'alternate_contact_phone',
        'products_services',
        'manufacturer_details',
        'manufacturer_or_distributor_status',
        'years_in_operation',
        'delivery_coverage_areas',
        'quality_management_notes',
        'regulated_supplier',
        'compliance_status',
        'award_eligibility',
        'is_preferred',
        'restriction_reason',
        'restriction_expires_at',
        'status_changed_by',
        'status_changed_at',
        'portal_status',
        'review_comments',
        'submitted_at',
        'verified_at',
        'verified_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'vat_registered' => 'boolean',
        'regulated_supplier' => 'boolean',
        'is_preferred' => 'boolean',
        'business_license_expiry_date' => 'date',
        'tax_clearance_expiry_date' => 'date',
        'restriction_expires_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SupplierCategory::class, 'supplier_category_supplier');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function tenderResponses(): HasMany
    {
        return $this->hasMany(TenderResponse::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function performanceEvaluations(): HasMany
    {
        return $this->hasMany(SupplierPerformanceEvaluation::class);
    }

    public function performanceIncidents(): HasMany
    {
        return $this->hasMany(SupplierPerformanceIncident::class);
    }

    public function performanceOverrides(): HasMany
    {
        return $this->hasMany(SupplierPerformanceOverride::class);
    }

    public function currentPerformance(): HasOne
    {
        return $this->hasOne(SupplierPerformanceEvaluation::class)->latestOfMany('calculated_at');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }
}
