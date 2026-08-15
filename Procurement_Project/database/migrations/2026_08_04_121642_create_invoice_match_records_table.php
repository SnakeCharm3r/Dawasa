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
        Schema::create('invoice_match_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('goods_receipt_note_id')->nullable()->constrained('goods_receipt_notes')->nullOnDelete();
            $table->enum('match_status', ['matched', 'quantity_variance', 'price_variance', 'missing_grn', 'failed']);
            $table->decimal('po_amount', 15, 2);
            $table->decimal('grn_accepted_amount', 15, 2);
            $table->decimal('invoice_amount', 15, 2);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->text('variance_reason')->nullable();
            $table->foreignId('matched_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('matched_at');
            $table->timestamps();

            $table->index(['supplier_invoice_id', 'match_status']);
            $table->index(['purchase_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_match_records');
    }
};
