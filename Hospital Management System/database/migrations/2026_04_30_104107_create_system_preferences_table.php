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
        Schema::create('system_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_name')->default('Hospital Management System');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->json('social_media')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->default('Y-m-d');
            $table->string('currency')->default('USD');
            $table->integer('items_per_page')->default(10);
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_preferences');
    }
};
