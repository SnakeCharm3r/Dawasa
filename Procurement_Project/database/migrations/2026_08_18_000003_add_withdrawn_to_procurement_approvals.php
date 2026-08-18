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
            DB::statement("ALTER TABLE procurement_approvals MODIFY action ENUM('recommendation_submitted','selection_submitted','selection_approved','selection_rejected','approved','returned_to_sourcing','rejected','requester_returned','line_manager_returned','line_manager_approved','withdrawn') NOT NULL");
        } else {
            Schema::table('procurement_approvals', function (Blueprint $table) {
                $table->enum('action', [
                    'recommendation_submitted',
                    'selection_submitted',
                    'selection_approved',
                    'selection_rejected',
                    'approved',
                    'returned_to_sourcing',
                    'rejected',
                    'requester_returned',
                    'line_manager_returned',
                    'line_manager_approved',
                    'withdrawn',
                ])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE procurement_approvals MODIFY action ENUM('recommendation_submitted','selection_submitted','selection_approved','selection_rejected','approved','returned_to_sourcing','rejected','requester_returned','line_manager_returned','line_manager_approved') NOT NULL");
        } else {
            Schema::table('procurement_approvals', function (Blueprint $table) {
                $table->enum('action', [
                    'recommendation_submitted',
                    'selection_submitted',
                    'selection_approved',
                    'selection_rejected',
                    'approved',
                    'returned_to_sourcing',
                    'rejected',
                    'requester_returned',
                    'line_manager_returned',
                    'line_manager_approved',
                ])->change();
            });
        }
    }
};
