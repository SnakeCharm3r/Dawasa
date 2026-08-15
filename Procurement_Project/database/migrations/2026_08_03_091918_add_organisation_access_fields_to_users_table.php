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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');
            $table->foreignId('department_id')
                ->after('last_name')
                ->constrained('departments')
                ->restrictOnDelete();
            $table->foreignId('line_manager_id')
                ->nullable()
                ->after('department_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('job_title')->after('line_manager_id');
            $table->boolean('is_line_manager')->default(false)->after('job_title');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['line_manager_id']);
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'department_id',
                'line_manager_id',
                'job_title',
                'is_line_manager',
                'phone',
                'is_active',
            ]);
        });
    }
};
