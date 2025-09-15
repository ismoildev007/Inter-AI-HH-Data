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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('industry_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('experience_level')->nullable();
            $table->decimal('desired_salary_from', 15, 2)->nullable();
            $table->decimal('desired_salary_to', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('work_mode')->nullable();
            $table->string('notes')->nullable();
            $table->text('cover_letter')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
