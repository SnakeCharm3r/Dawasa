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
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (! Schema::hasColumn('departments', 'business_entity_id')) {
                    $table->foreignId('business_entity_id')
                        ->constrained('business_entities')
                        ->restrictOnDelete();
                }

                if (! Schema::hasColumn('departments', 'name')) {
                    $table->string('name');
                }

                if (! Schema::hasColumn('departments', 'code')) {
                    $table->string('code');
                }

                if (! Schema::hasColumn('departments', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (! Schema::hasColumn('departments', 'created_at') || ! Schema::hasColumn('departments', 'updated_at')) {
                    $table->timestamps();
                }
            });

            if (! $this->indexExists('departments', 'departments_business_entity_id_code_unique')) {
                Schema::table('departments', function (Blueprint $table) {
                    $table->unique(['business_entity_id', 'code'], 'departments_business_entity_id_code_unique');
                });
            }

            return;
        }

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_entity_id')
                ->constrained('business_entities')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_entity_id', 'code'], 'departments_business_entity_id_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        if ($this->indexExists('departments', 'departments_business_entity_id_code_unique')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropUnique('departments_business_entity_id_code_unique');
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'business_entity_id')) {
                try {
                    $table->dropForeign(['business_entity_id']);
                } catch (Throwable $e) {
                    // Ignore if the foreign key does not exist.
                }

                $table->dropColumn('business_entity_id');
            }

            if (Schema::hasColumn('departments', 'code')) {
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('departments', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('departments', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, [$indexName]);
    }
};
