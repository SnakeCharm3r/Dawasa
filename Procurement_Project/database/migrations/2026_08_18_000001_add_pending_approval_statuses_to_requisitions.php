<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','returned','rejected','approved_for_sourcing','cancelled','quotations_ready','pending_requester_approval','pending_line_manager_approval','pending_final_approval','pending_gm_approval','approved_for_purchase','returned_to_sourcing') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->enum('status', [
                    'draft',
                    'submitted',
                    'returned',
                    'rejected',
                    'approved_for_sourcing',
                    'cancelled',
                    'quotations_ready',
                    'pending_requester_approval',
                    'pending_line_manager_approval',
                    'pending_final_approval',
                    'pending_gm_approval',
                    'approved_for_purchase',
                    'returned_to_sourcing',
                ])->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','returned','rejected','approved_for_sourcing','cancelled','quotations_ready','pending_final_approval','pending_gm_approval','approved_for_purchase','returned_to_sourcing') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->enum('status', [
                    'draft',
                    'submitted',
                    'returned',
                    'rejected',
                    'approved_for_sourcing',
                    'cancelled',
                    'quotations_ready',
                    'pending_final_approval',
                    'pending_gm_approval',
                    'approved_for_purchase',
                    'returned_to_sourcing',
                ])->default('draft')->change();
            });
        }
    }
};
