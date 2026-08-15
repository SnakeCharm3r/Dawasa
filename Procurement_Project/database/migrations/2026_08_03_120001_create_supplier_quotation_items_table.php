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
        Schema::create('supplier_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_quotation_id')->constrained('supplier_quotations')->restrictOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_quotation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotation_items');
    }
};
