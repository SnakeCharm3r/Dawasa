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
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number')->unique();
            $table->foreignId('business_entity_id')->constrained('business_entities')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('line_manager_id')->constrained('users')->restrictOnDelete();
            $table->date('required_date');
            $table->text('purpose');
            $table->decimal('estimated_amount', 15, 2);
            $table->decimal('committed_amount', 15, 2)->nullable()->default(0);
            $table->enum('status', ['draft', 'submitted', 'returned', 'rejected', 'approved_for_sourcing', 'cancelled', 'quotations_ready', 'pending_final_approval', 'approved_for_purchase', 'returned_to_sourcing'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('estimate_difference_reason')->nullable();
            $table->timestamps();

            $table->index(['business_entity_id', 'department_id', 'requester_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requisitions');
    }
};
