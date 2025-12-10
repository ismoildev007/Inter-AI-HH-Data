<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('country');
            $table->unsignedSmallInteger('birth_year')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_year']);
        });
    }
};

