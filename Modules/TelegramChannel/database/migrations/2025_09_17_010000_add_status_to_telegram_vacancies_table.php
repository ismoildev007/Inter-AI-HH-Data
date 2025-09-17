<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_vacancies', 'status')) {
                $table->string('status', 20)->default('publish')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_vacancies', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_vacancies', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

