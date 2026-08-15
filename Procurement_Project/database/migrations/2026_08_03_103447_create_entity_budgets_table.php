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
        Schema::create('entity_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')
                ->constrained('business_entities')
                ->restrictOnDelete();
            $table->foreignId('financial_year_id')
                ->constrained('financial_years')
                ->restrictOnDelete();
            $table->decimal('proposed_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'returned', 'approved', 'rejected', 'closed'])
                ->default('draft');
            $table->foreignId('proposed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_comments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_entity_id', 'financial_year_id']);
            $table->index(['status', 'business_entity_id', 'financial_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_budgets');
    }
};
