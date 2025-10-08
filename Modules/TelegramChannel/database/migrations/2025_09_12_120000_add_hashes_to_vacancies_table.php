<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (!Schema::hasColumn('vacancies', 'raw_hash')) {
                $table->string('raw_hash', 64)->nullable()->after('signature');
            }
            if (!Schema::hasColumn('vacancies', 'normalized_hash')) {
                $table->string('normalized_hash', 64)->nullable()->after('raw_hash');
            }
            $table->index('raw_hash', 'vacancies_raw_hash_idx');
            $table->index('normalized_hash', 'vacancies_normalized_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            if (Schema::hasColumn('vacancies', 'raw_hash')) {
                $table->dropIndex('vacancies_raw_hash_idx');
                $table->dropColumn('raw_hash');
            }
            if (Schema::hasColumn('vacancies', 'normalized_hash')) {
                $table->dropIndex('vacancies_normalized_hash_idx');
                $table->dropColumn('normalized_hash');
            }
        });
    }
};
