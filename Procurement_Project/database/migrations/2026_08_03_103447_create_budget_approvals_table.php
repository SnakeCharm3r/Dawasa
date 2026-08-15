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
        Schema::create('budget_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_budget_id')
                ->constrained('entity_budgets')
                ->restrictOnDelete();
            $table->enum('action', ['submitted', 'approved', 'returned', 'rejected', 'revised']);
            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index('entity_budget_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_approvals');
    }
};
