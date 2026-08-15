<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->restrictOnDelete();
            $table->foreignId('quotation_recommendation_id')->constrained('quotation_recommendations')->restrictOnDelete();
            $table->enum('action', ['recommendation_submitted', 'approved', 'returned_to_sourcing', 'rejected']);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['purchase_requisition_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_approvals');
    }
};
