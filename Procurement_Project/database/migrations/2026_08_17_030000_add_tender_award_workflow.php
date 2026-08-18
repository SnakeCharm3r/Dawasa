<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->foreignId('closed_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('closed_by');
            $table->unsignedBigInteger('winning_tender_response_id')->nullable()->after('closed_at')->index();
            $table->foreignId('awarded_by')->nullable()->after('winning_tender_response_id')->constrained('users')->nullOnDelete();
            $table->timestamp('awarded_at')->nullable()->after('awarded_by');
            $table->text('award_comments')->nullable()->after('awarded_at');
        });

        Schema::table('tender_responses', function (Blueprint $table) {
            $table->string('award_status', 30)->default('pending')->after('status')->index();
            $table->timestamp('award_notified_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tender_responses', function (Blueprint $table) {
            $table->dropIndex(['award_status']);
            $table->dropColumn(['award_status', 'award_notified_at']);
        });

        Schema::table('tenders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropIndex(['winning_tender_response_id']);
            $table->dropConstrainedForeignId('awarded_by');
            $table->dropColumn(['closed_at', 'winning_tender_response_id', 'awarded_at', 'award_comments']);
        });
    }
};
