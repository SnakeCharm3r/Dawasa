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
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->nullable()->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('business_entity_id')->constrained('business_entities')->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->date('received_date');
            $table->string('delivery_note_number')->nullable();
            $table->string('supplier_invoice_reference')->nullable();
            $table->enum('delivery_condition', ['good', 'damaged', 'partial', 'rejected'])->default('good');
            $table->enum('status', ['draft', 'submitted', 'inspected', 'partially_accepted', 'accepted', 'rejected', 'cancelled'])->default('draft');
            $table->boolean('inspection_required')->default(true);
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->text('inspection_comments')->nullable();
            $table->string('received_location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'business_entity_id']);
            $table->index(['received_date']);
            $table->index(['purchase_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
