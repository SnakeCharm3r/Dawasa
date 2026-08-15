<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->restrictOnDelete();
            $table->foreignId('selected_quotation_id')->constrained('supplier_quotations')->restrictOnDelete();
            $table->foreignId('recommended_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recommended_at')->nullable();
            $table->text('reason_for_selection');
            $table->text('non_lowest_price_reason')->nullable();
            $table->decimal('total_quoted_amount', 15, 2);
            $table->enum('status', ['draft', 'submitted', 'approved', 'returned', 'rejected'])->default('draft');
            $table->timestamps();

            $table->index('purchase_requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_recommendations');
    }
};
