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
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->restrictOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_requisition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_items');
    }
};
