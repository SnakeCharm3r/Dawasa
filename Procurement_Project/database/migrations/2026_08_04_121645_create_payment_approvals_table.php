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
        Schema::create('payment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_voucher_id')->constrained('payment_vouchers')->cascadeOnDelete();
            $table->enum('action', ['created', 'submitted', 'approved', 'returned', 'rejected', 'paid', 'cancelled']);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['payment_voucher_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_approvals');
    }
};
