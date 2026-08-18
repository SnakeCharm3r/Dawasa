<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supplierColumns = Schema::getColumnListing('suppliers');
        Schema::table('suppliers', function (Blueprint $table) use ($supplierColumns) {
            $add = function (string $column, callable $definition) use ($supplierColumns, $table): void {
                if (! in_array($column, $supplierColumns, true)) {
                    $definition($table);
                }
            };

            $add('legal_name', fn (Blueprint $table) => $table->string('legal_name')->nullable()->after('name'));
            $add('brela_registration_number', fn (Blueprint $table) => $table->string('brela_registration_number', 100)->nullable());
            $add('incorporation_or_compliance_number', fn (Blueprint $table) => $table->string('incorporation_or_compliance_number', 100)->nullable());
            $add('business_license_number', fn (Blueprint $table) => $table->string('business_license_number', 100)->nullable());
            $add('business_license_issuing_authority', fn (Blueprint $table) => $table->string('business_license_issuing_authority')->nullable());
            $add('business_license_expiry_date', fn (Blueprint $table) => $table->date('business_license_expiry_date')->nullable());
            $add('tin_number', fn (Blueprint $table) => $table->string('tin_number', 100)->nullable());
            $add('vat_registered', fn (Blueprint $table) => $table->boolean('vat_registered')->default(false));
            $add('vat_registration_number', fn (Blueprint $table) => $table->string('vat_registration_number', 100)->nullable());
            $add('tax_clearance_number', fn (Blueprint $table) => $table->string('tax_clearance_number', 100)->nullable());
            $add('tax_clearance_expiry_date', fn (Blueprint $table) => $table->date('tax_clearance_expiry_date')->nullable());
            $add('physical_office_address', fn (Blueprint $table) => $table->text('physical_office_address')->nullable());
            $add('building_plot_street', fn (Blueprint $table) => $table->string('building_plot_street')->nullable());
            $add('ward', fn (Blueprint $table) => $table->string('ward', 100)->nullable());
            $add('district', fn (Blueprint $table) => $table->string('district', 100)->nullable());
            $add('postal_address', fn (Blueprint $table) => $table->text('postal_address')->nullable());
            $add('primary_contact_name', fn (Blueprint $table) => $table->string('primary_contact_name')->nullable());
            $add('primary_contact_position', fn (Blueprint $table) => $table->string('primary_contact_position')->nullable());
            $add('primary_contact_phone', fn (Blueprint $table) => $table->string('primary_contact_phone', 50)->nullable());
            $add('primary_contact_email', fn (Blueprint $table) => $table->string('primary_contact_email')->nullable());
            $add('alternate_contact_name', fn (Blueprint $table) => $table->string('alternate_contact_name')->nullable());
            $add('alternate_contact_phone', fn (Blueprint $table) => $table->string('alternate_contact_phone', 50)->nullable());
            $add('manufacturer_or_distributor_status', fn (Blueprint $table) => $table->string('manufacturer_or_distributor_status', 100)->nullable());
            $add('years_in_operation', fn (Blueprint $table) => $table->unsignedSmallInteger('years_in_operation')->nullable());
            $add('delivery_coverage_areas', fn (Blueprint $table) => $table->text('delivery_coverage_areas')->nullable());
            $add('quality_management_notes', fn (Blueprint $table) => $table->text('quality_management_notes')->nullable());
            $add('regulated_supplier', fn (Blueprint $table) => $table->boolean('regulated_supplier')->default(false));
            $add('compliance_status', fn (Blueprint $table) => $table->string('compliance_status', 40)->default('incomplete')->index());
            $add('award_eligibility', fn (Blueprint $table) => $table->string('award_eligibility', 30)->default('restricted')->index());
            $add('is_preferred', fn (Blueprint $table) => $table->boolean('is_preferred')->default(false));
            $add('restriction_reason', fn (Blueprint $table) => $table->text('restriction_reason')->nullable());
            $add('restriction_expires_at', fn (Blueprint $table) => $table->timestamp('restriction_expires_at')->nullable());
            $add('status_changed_by', fn (Blueprint $table) => $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete());
            $add('status_changed_at', fn (Blueprint $table) => $table->timestamp('status_changed_at')->nullable());
        });

        DB::table('suppliers')->update([
            'legal_name' => DB::raw('name'),
            'tin_number' => DB::raw('tax_number'),
            'vat_registration_number' => DB::raw('vat_number'),
            'physical_office_address' => DB::raw('address'),
            'primary_contact_name' => DB::raw('contact_person'),
            'primary_contact_position' => DB::raw('contact_position'),
            'primary_contact_phone' => DB::raw('phone'),
            'primary_contact_email' => DB::raw('email'),
            'alternate_contact_phone' => DB::raw('alternate_phone'),
        ]);

        $documentColumns = Schema::getColumnListing('supplier_documents');
        Schema::table('supplier_documents', function (Blueprint $table) use ($documentColumns) {
            $add = function (string $column, callable $definition) use ($documentColumns, $table): void {
                if (! in_array($column, $documentColumns, true)) {
                    $definition($table);
                }
            };

            $add('document_number', fn (Blueprint $table) => $table->string('document_number', 100)->nullable());
            $add('issue_date', fn (Blueprint $table) => $table->date('issue_date')->nullable());
            $add('expiry_date', fn (Blueprint $table) => $table->date('expiry_date')->nullable());
            $add('file_path', fn (Blueprint $table) => $table->string('file_path')->nullable());
            $add('original_filename', fn (Blueprint $table) => $table->string('original_filename')->nullable());
            $add('verification_status', fn (Blueprint $table) => $table->string('verification_status', 30)->default('pending')->index());
            $add('verification_notes', fn (Blueprint $table) => $table->text('verification_notes')->nullable());
            $add('verified_by', fn (Blueprint $table) => $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete());
            $add('verified_at', fn (Blueprint $table) => $table->timestamp('verified_at')->nullable());
        });

        DB::table('supplier_documents')->update([
            'expiry_date' => DB::raw('expires_at'),
            'file_path' => DB::raw('storage_path'),
            'original_filename' => DB::raw('original_name'),
            'verification_status' => DB::raw("CASE WHEN status = 'pending_verification' THEN 'pending' ELSE status END"),
            'verification_notes' => DB::raw('review_comments'),
            'verified_by' => DB::raw('reviewed_by'),
            'verified_at' => DB::raw('reviewed_at'),
        ]);

        if (! Schema::hasTable('supplier_performance_evaluations')) {
            Schema::create('supplier_performance_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $table->foreignId('business_entity_id')->nullable()->constrained('business_entities')->nullOnDelete();
                $table->date('evaluation_period_start');
                $table->date('evaluation_period_end');
                $table->unsignedInteger('purchase_orders_count')->default(0);
                $table->unsignedInteger('completed_purchase_orders_count')->default(0);
                $table->unsignedInteger('cancelled_purchase_orders_count')->default(0);
                $table->decimal('total_awarded_value', 18, 2)->nullable();
                $table->decimal('delivery_score', 5, 2);
                $table->decimal('quality_score', 5, 2);
                $table->decimal('compliance_score', 5, 2);
                $table->decimal('responsiveness_score', 5, 2);
                $table->decimal('commercial_reliability_score', 5, 2);
                $table->decimal('overall_score', 5, 2);
                $table->string('grade', 30);
                $table->timestamp('calculated_at');
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('calculation_version', 30);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['supplier_id', 'calculated_at']);
                $table->index(['business_entity_id', 'grade']);
            });
        }

        if (! Schema::hasTable('supplier_performance_incidents')) {
            Schema::create('supplier_performance_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->foreignId('goods_receipt_note_id')->nullable()->constrained('goods_receipt_notes')->nullOnDelete();
                $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();
                $table->string('incident_type', 40);
                $table->string('severity', 20);
                $table->text('description');
                $table->timestamp('occurred_at');
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['supplier_id', 'resolved_at', 'severity']);
            });
        }

        if (! Schema::hasTable('supplier_performance_overrides')) {
            Schema::create('supplier_performance_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $table->string('eligibility', 30);
                $table->text('reason');
                $table->timestamp('expires_at');
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['supplier_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_overrides');
        Schema::dropIfExists('supplier_performance_incidents');
        Schema::dropIfExists('supplier_performance_evaluations');

        Schema::table('supplier_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['document_number', 'issue_date', 'expiry_date', 'file_path', 'original_filename', 'verification_status', 'verification_notes', 'verified_at']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropColumn([
                'legal_name', 'brela_registration_number', 'incorporation_or_compliance_number', 'business_license_number',
                'business_license_issuing_authority', 'business_license_expiry_date', 'tin_number', 'vat_registered',
                'vat_registration_number', 'tax_clearance_number', 'tax_clearance_expiry_date', 'physical_office_address',
                'building_plot_street', 'ward', 'district', 'postal_address', 'primary_contact_name',
                'primary_contact_position', 'primary_contact_phone', 'primary_contact_email', 'alternate_contact_name',
                'alternate_contact_phone', 'manufacturer_or_distributor_status', 'years_in_operation',
                'delivery_coverage_areas', 'quality_management_notes', 'regulated_supplier', 'compliance_status',
                'award_eligibility', 'is_preferred', 'restriction_reason', 'restriction_expires_at', 'status_changed_at',
            ]);
        });
    }
};
