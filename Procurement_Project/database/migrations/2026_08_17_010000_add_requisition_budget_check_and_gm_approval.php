<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->string('budget_check_status', 40)->nullable()->after('committed_amount');
            $table->decimal('budget_available_at_check', 15, 2)->nullable()->after('budget_check_status');
            $table->decimal('budget_shortfall_amount', 15, 2)->default(0)->after('budget_available_at_check');
            $table->timestamp('budget_checked_at')->nullable()->after('budget_shortfall_amount');
            $table->boolean('budget_shortfall_acknowledged')->default(false)->after('budget_checked_at');
            $table->timestamp('budget_shortfall_acknowledged_at')->nullable()->after('budget_shortfall_acknowledged');
            $table->foreignId('budget_shortfall_acknowledged_by')->nullable()->after('budget_shortfall_acknowledged_at')->constrained('users')->nullOnDelete();
            $table->text('budget_shortfall_reason')->nullable()->after('budget_shortfall_acknowledged_by');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','pending_gm_approval','returned','rejected','approved_for_sourcing','cancelled','quotations_ready','pending_final_approval','approved_for_purchase','returned_to_sourcing') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE requisition_approvals MODIFY action ENUM('submitted','line_manager_approved','gm_approved','approved','returned','rejected','cancelled') NOT NULL");
        } else {
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'pending_gm_approval', 'returned', 'rejected', 'approved_for_sourcing', 'cancelled', 'quotations_ready', 'pending_final_approval', 'approved_for_purchase', 'returned_to_sourcing'])->default('draft')->change();
            });
            Schema::table('requisition_approvals', function (Blueprint $table) {
                $table->enum('action', ['submitted', 'line_manager_approved', 'gm_approved', 'approved', 'returned', 'rejected', 'cancelled'])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE purchase_requisitions SET status = 'submitted' WHERE status = 'pending_gm_approval'");
            DB::statement("UPDATE requisition_approvals SET action = 'approved' WHERE action IN ('line_manager_approved','gm_approved')");
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','returned','rejected','approved_for_sourcing','cancelled','quotations_ready','pending_final_approval','approved_for_purchase','returned_to_sourcing') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE requisition_approvals MODIFY action ENUM('submitted','approved','returned','rejected','cancelled') NOT NULL");
        } else {
            DB::table('purchase_requisitions')->where('status', 'pending_gm_approval')->update(['status' => 'submitted']);
            DB::table('requisition_approvals')->whereIn('action', ['line_manager_approved', 'gm_approved'])->update(['action' => 'approved']);
            Schema::table('purchase_requisitions', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'returned', 'rejected', 'approved_for_sourcing', 'cancelled', 'quotations_ready', 'pending_final_approval', 'approved_for_purchase', 'returned_to_sourcing'])->default('draft')->change();
            });
            Schema::table('requisition_approvals', function (Blueprint $table) {
                $table->enum('action', ['submitted', 'approved', 'returned', 'rejected', 'cancelled'])->change();
            });
        }

        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_shortfall_acknowledged_by');
            $table->dropColumn([
                'budget_check_status',
                'budget_available_at_check',
                'budget_shortfall_amount',
                'budget_checked_at',
                'budget_shortfall_acknowledged',
                'budget_shortfall_acknowledged_at',
                'budget_shortfall_reason',
            ]);
        });
    }
};
