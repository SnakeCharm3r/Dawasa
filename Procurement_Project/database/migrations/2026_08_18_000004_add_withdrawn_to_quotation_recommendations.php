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
            DB::statement("ALTER TABLE quotation_recommendations MODIFY status ENUM('draft','submitted','approved','returned','rejected','withdrawn') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('quotation_recommendations', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'returned', 'rejected', 'withdrawn'])->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quotation_recommendations MODIFY status ENUM('draft','submitted','approved','returned','rejected') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('quotation_recommendations', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'returned', 'rejected'])->default('draft')->change();
            });
        }
    }
};
