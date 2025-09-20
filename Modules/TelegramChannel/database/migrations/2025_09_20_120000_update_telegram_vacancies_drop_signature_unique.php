<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_vacancies', function (Blueprint $table) {
            // Drop unique index on signature to allow multiple rows (archive + new publish)
            try {
                $table->dropUnique('tg_vac_signature_unique');
            } catch (\Throwable $e) {
                // Index may not exist in some environments
            }
            // Add a regular index for faster lookups by signature
            try {
                $table->index('signature', 'tg_vac_signature_index');
            } catch (\Throwable $e) {
                // Ignore if already exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_vacancies', function (Blueprint $table) {
            try {
                $table->dropIndex('tg_vac_signature_index');
            } catch (\Throwable $e) {}
            try {
                $table->unique('signature', 'tg_vac_signature_unique');
            } catch (\Throwable $e) {}
        });
    }
};

