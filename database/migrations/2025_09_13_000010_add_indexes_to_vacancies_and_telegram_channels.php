<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('vacancies', 'status')) {
                $table->string('status', 32)->default('publish')->index();
            }
            $table->index(['status', 'created_at'], 'vacancies_status_created_at_idx');
            $table->unique('external_id', 'vacancies_external_id_unique');
        });

        if (Schema::hasTable('telegram_channels')) {
            Schema::table('telegram_channels', function (Blueprint $table) {
                $table->index('channel_id', 'tg_channels_channel_id_idx');
                $table->index('is_source', 'tg_channels_is_source_idx');
                $table->index('is_target', 'tg_channels_is_target_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex('vacancies_status_created_at_idx');
            $table->dropUnique('vacancies_external_id_unique');
        });
        if (Schema::hasTable('telegram_channels')) {
            Schema::table('telegram_channels', function (Blueprint $table) {
                $table->dropIndex('tg_channels_channel_id_idx');
                $table->dropIndex('tg_channels_is_source_idx');
                $table->dropIndex('tg_channels_is_target_idx');
            });
        }
    }
};

