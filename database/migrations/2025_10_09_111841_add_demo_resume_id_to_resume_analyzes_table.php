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
        Schema::table('resume_analyzes', function (Blueprint $table) {
            $table->unsignedBigInteger('demo_resume_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resume_analyzes', function (Blueprint $table) {
            $table->dropColumn('demo_resume_id');
        });
    }
};
