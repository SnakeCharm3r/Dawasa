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
        Schema::create('goods_receipt_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->enum('action', ['created', 'submitted', 'inspected', 'partially_accepted', 'accepted', 'rejected', 'cancelled']);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['goods_receipt_note_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_approvals');
    }
};
