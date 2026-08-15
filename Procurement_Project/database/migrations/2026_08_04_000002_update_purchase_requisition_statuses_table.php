<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','returned','rejected','approved_for_sourcing','cancelled','quotations_ready','pending_final_approval','approved_for_purchase','returned_to_sourcing') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM('draft','submitted','returned','rejected','approved_for_sourcing','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
