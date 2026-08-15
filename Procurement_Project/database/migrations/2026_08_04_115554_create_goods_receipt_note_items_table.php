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
        Schema::create('goods_receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->string('item_name');
            $table->text('specification')->nullable();
            $table->decimal('quantity_ordered', 12, 2);
            $table->decimal('quantity_previously_received', 12, 2)->default(0);
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_accepted', 12, 2)->default(0);
            $table->decimal('quantity_rejected', 12, 2)->default(0);
            $table->string('unit');
            $table->enum('condition_status', ['pending', 'accepted', 'partially_accepted', 'rejected', 'damaged'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->timestamps();

            $table->index('goods_receipt_note_id');
            $table->index('purchase_order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_items');
    }
};
