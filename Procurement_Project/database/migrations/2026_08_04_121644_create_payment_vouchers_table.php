<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->nullable()->unique();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('business_entity_id')->constrained('business_entities')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'cheque', 'cash', 'mobile_money', 'other']);
            $table->string('payment_reference')->nullable();
            $table->decimal('amount_requested', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_approval',
                'approved',
                'returned',
                'rejected',
                'paid',
                'cancelled',
            ])->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('comments')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'business_entity_id']);
            $table->index(['payment_date']);
            $table->index(['supplier_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
