<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mock_interviews', function (Blueprint $table) {

            if (!Schema::hasColumn('mock_interviews', 'overall_percentage')) {
                $table->integer('overall_percentage')
                    ->nullable()
                    ->after('overall_score');
            }

            if (!Schema::hasColumn('mock_interviews', 'strengths')) {
                $table->json('strengths')
                    ->nullable()
                    ->after('overall_percentage');
            }

            if (!Schema::hasColumn('mock_interviews', 'weaknesses')) {
                $table->json('weaknesses')
                    ->nullable()
                    ->after('strengths');
            }

            if (!Schema::hasColumn('mock_interviews', 'work_on')) {
                $table->json('work_on')
                    ->nullable()
                    ->after('weaknesses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mock_interviews', function (Blueprint $table) {
            $table->dropColumn([
                'overall_percentage',
                'strengths',
                'weaknesses',
                'work_on'
            ]);
        });
    }
};
