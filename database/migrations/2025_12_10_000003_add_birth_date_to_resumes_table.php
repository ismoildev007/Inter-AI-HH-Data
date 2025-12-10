<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            // Tug'ilgan sana to'liq ko'rinishda (masalan, 17-12-2000) saqlash uchun
            $table->string('birth_date', 20)->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};

