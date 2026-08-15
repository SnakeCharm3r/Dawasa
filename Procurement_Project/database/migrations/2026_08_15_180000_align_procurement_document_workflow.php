<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('suppliers', 'deleted_at')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        Schema::table('supplier_quotations', function (Blueprint $table) {
            $table->foreignId('rejected_by')->nullable()->after('expired_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('rejected_by')->nullable()->after('accountant_confirmed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        DB::table('supplier_invoices')->where('status', 'rejected')->update([
            'status' => 'returned',
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE supplier_quotations MODIFY status ENUM('draft','active','withdrawn','expired','rejected') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft','pending_accountant_confirmation','confirmed','issued','acknowledged','partially_received','fully_received','rejected','cancelled','closed') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE purchase_order_approvals MODIFY action ENUM('created','submitted_for_confirmation','accountant_confirmed','accountant_rejected','issued','acknowledged','cancelled') NOT NULL");
            DB::statement("ALTER TABLE supplier_invoices MODIFY status ENUM('draft','submitted','pending_match','matched','matched_with_variance','returned','approved_for_payment','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft'");
        } elseif (DB::getDriverName() === 'sqlite') {
            Schema::table('purchase_order_approvals', function (Blueprint $table) {
                $table->string('action')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('supplier_quotations')->where('status', 'rejected')->update(['status' => 'withdrawn']);
            DB::table('purchase_orders')->where('status', 'rejected')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE supplier_quotations MODIFY status ENUM('draft','active','withdrawn','expired') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft','pending_accountant_confirmation','confirmed','issued','acknowledged','partially_received','fully_received','cancelled','closed') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE purchase_order_approvals MODIFY action ENUM('created','submitted_for_confirmation','accountant_confirmed','issued','acknowledged','cancelled') NOT NULL");
            DB::statement("ALTER TABLE supplier_invoices MODIFY status ENUM('draft','submitted','pending_match','matched','matched_with_variance','returned','rejected','approved_for_payment','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('supplier_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });

    }
};
