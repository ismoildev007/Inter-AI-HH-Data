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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_trial_active')
                ->default(false)
                ->index();
            $table->dateTime('trial_start_date')
                ->nullable()
                ->index();
            $table->dateTime('trial_end_date')
                ->nullable()
                ->index();
            $table->string('status', 32)
                ->default('not working')
                ->index();
            $table->boolean('admin_check_status')
                ->default(false)
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_trial_active',
                'trial_start_date',
                'trial_end_date',
                'status',
                'admin_check_status',
            ]);
        });
    }
};

