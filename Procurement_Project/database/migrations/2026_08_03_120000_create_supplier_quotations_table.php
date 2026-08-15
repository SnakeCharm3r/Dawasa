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
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('quotation_number')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'active', 'withdrawn', 'expired'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_requisition_id', 'supplier_id', 'quotation_number'], 'supplier_quotation_unique');
            $table->index(['purchase_requisition_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};
