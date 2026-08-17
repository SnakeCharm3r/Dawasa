<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
            $table->string('job_title')->nullable()->change();
        });

        if (! Schema::hasTable('supplier_categories')) {
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        if (! Schema::hasColumn('suppliers', 'user_id')) {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
            $table->string('application_reference', 50)->nullable()->unique()->after('code');
            $table->string('trading_name')->nullable()->after('name');
            $table->string('vat_number', 50)->nullable()->after('tax_number');
            $table->string('supplier_type', 50)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 100)->default('Tanzania');
            $table->string('website')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->text('products_services')->nullable();
            $table->text('manufacturer_details')->nullable();
            $table->string('portal_status', 40)->default('approved')->index();
            $table->text('review_comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
        }

        if (! Schema::hasTable('supplier_category_supplier')) {
        Schema::create('supplier_category_supplier', function (Blueprint $table) {
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['supplier_id', 'supplier_category_id']);
        });
        }

        if (! Schema::hasTable('supplier_documents')) {
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 60);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->date('expires_at')->nullable();
            $table->string('status', 30)->default('pending_verification');
            $table->text('review_comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'document_type']);
        });
        }

        if (! Schema::hasColumn('purchase_requisitions', 'supplier_category_id')) {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->foreignId('supplier_category_id')->nullable()->constrained()->nullOnDelete();
        });
        }

        if (! Schema::hasTable('tenders')) {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tender_number', 50)->unique();
            $table->string('title');
            $table->text('public_summary');
            $table->string('tender_type', 30);
            $table->string('visibility', 30);
            $table->timestamp('publication_at')->nullable();
            $table->timestamp('submission_deadline')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone', 30)->nullable();
            $table->text('eligibility_requirements')->nullable();
            $table->text('submission_instructions')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('tender_items')) {
        Schema::create('tender_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_requisition_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 50);
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('tender_invitations')) {
        Schema::create('tender_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->unique(['tender_id', 'supplier_id']);
        });
        }

        if (! Schema::hasTable('tender_responses')) {
        Schema::create('tender_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->string('quotation_number', 50);
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->unsignedInteger('delivery_period_days')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->text('supplier_comments')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->text('compliance_comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['tender_id', 'supplier_id']);
        });
        }

        if (! Schema::hasTable('tender_response_items')) {
        Schema::create('tender_response_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_item_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_price', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->string('brand_make')->nullable();
            $table->text('offered_specification')->nullable();
            $table->timestamps();
            $table->unique(['tender_response_id', 'tender_item_id']);
        });
        }

        if (! Schema::hasTable('tender_response_documents')) {
        Schema::create('tender_response_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_response_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_response_documents');
        Schema::dropIfExists('tender_response_items');
        Schema::dropIfExists('tender_responses');
        Schema::dropIfExists('tender_invitations');
        Schema::dropIfExists('tender_items');
        Schema::dropIfExists('tenders');
        Schema::table('purchase_requisitions', fn (Blueprint $table) => $table->dropConstrainedForeignId('supplier_category_id'));
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('supplier_category_supplier');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['application_reference', 'trading_name', 'vat_number', 'supplier_type', 'region', 'country', 'website', 'contact_position', 'alternate_phone', 'products_services', 'manufacturer_details', 'portal_status', 'review_comments', 'submitted_at', 'verified_at']);
        });
        Schema::dropIfExists('supplier_categories');
    }
};
