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
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->foreignId('goods_receipt_note_item_id')->nullable()->constrained('goods_receipt_note_items')->nullOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity_invoiced', 12, 2);
            $table->decimal('quantity_previously_invoiced', 12, 2)->default(0);
            $table->decimal('quantity_accepted', 12, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->index('supplier_invoice_id');
            $table->index('purchase_order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_items');
    }
};
