<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_number')->nullable()->unique();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('quotation_recommendation_id')->nullable()->constrained('quotation_recommendations')->nullOnDelete();
            $table->foreignId('selected_quotation_id')->nullable()->constrained('supplier_quotations')->nullOnDelete();
            $table->foreignId('business_entity_id')->constrained('business_entities')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->enum('status', [
                'draft',
                'pending_accountant_confirmation',
                'confirmed',
                'issued',
                'acknowledged',
                'partially_received',
                'fully_received',
                'cancelled',
                'closed',
            ])->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency')->default('TZS');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('accountant_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accountant_confirmed_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('supplier_acknowledged_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique('purchase_requisition_id');
            $table->index(['status', 'business_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
