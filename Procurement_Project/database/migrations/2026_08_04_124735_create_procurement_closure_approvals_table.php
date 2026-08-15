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
        Schema::create('procurement_closure_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_closure_id');
            $table->enum('action', ['created', 'submitted_for_confirmation', 'requester_confirmed', 'closed', 'closed_with_exception', 'cancelled']);
            $table->unsignedBigInteger('actor_id');
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->foreign('procurement_closure_id')->references('id')->on('procurement_closures')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('restrict');

            $table->index('procurement_closure_id');
            $table->index('actor_id');
            $table->index('action_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_closure_approvals');
    }
};
