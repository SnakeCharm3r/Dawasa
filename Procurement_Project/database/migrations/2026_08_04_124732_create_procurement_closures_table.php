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
        Schema::create('procurement_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_requisition_id')->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->enum('closure_status', ['draft', 'pending_requester_confirmation', 'confirmed', 'closed_with_exception', 'cancelled'])->default('draft');
            $table->text('completion_summary')->nullable();
            $table->unsignedBigInteger('requester_confirmed_by')->nullable();
            $table->timestamp('requester_confirmed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('exception_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_requisition_id')->references('id')->on('purchase_requisitions')->onDelete('restrict');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('requester_confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('purchase_requisition_id');
            $table->index('purchase_order_id');
            $table->index('closure_status');
            $table->index('requester_confirmed_by');
            $table->index('closed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_closures');
    }
};
