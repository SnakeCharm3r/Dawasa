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
        if (Schema::hasIndex('quotation_recommendations', 'quotation_recommendations_purchase_requisition_id_unique')) {
            Schema::table('quotation_recommendations', function (Blueprint $table) {
                $table->dropUnique('quotation_recommendations_purchase_requisition_id_unique');
                $table->index('purchase_requisition_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('quotation_recommendations', 'quotation_recommendations_purchase_requisition_id_unique')) {
            Schema::table('quotation_recommendations', function (Blueprint $table) {
                $table->dropIndex('quotation_recommendations_purchase_requisition_id_index');
                $table->unique('purchase_requisition_id');
            });
        }
    }
};
