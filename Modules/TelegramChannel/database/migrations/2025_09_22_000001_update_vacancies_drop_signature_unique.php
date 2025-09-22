<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            try {
                $table->dropUnique('tg_vac_signature_unique');
            } catch (\Throwable $e) {
                // ignore if not exists
            }
            try {
                $table->index('signature', 'vac_signature_index');
            } catch (\Throwable $e) {
                // ignore if exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            try { $table->dropIndex('vac_signature_index'); } catch (\Throwable $e) {}
            try { $table->unique('signature', 'tg_vac_signature_unique'); } catch (\Throwable $e) {}
        });
    }
};

