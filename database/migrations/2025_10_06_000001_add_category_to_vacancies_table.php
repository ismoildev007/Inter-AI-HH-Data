<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('vacancies', 'category')) {
                $table->string('category', 64)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (Schema::hasColumn('vacancies', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
        });
    }
};

