<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->enum('action', [
                'created',
                'submitted_for_confirmation',
                'accountant_confirmed',
                'issued',
                'acknowledged',
                'cancelled',
            ]);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['purchase_order_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_approvals');
    }
};
