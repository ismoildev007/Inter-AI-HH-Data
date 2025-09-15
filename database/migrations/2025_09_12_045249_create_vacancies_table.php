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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable();
            $table->string('external_id')->nullable();
            $table->foreignId('employer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('hh_schedules')->onDelete('cascade');
            $table->foreignId('employment_id')->nullable()->constrained('hh_employments')->onDelete('cascade');
            $table->decimal('salary_from', 15, 2)->nullable();
            $table->decimal('salary_to', 15, 2)->nullable();
            $table->string('salary_currency', 10)->nullable();
            $table->string('salary_gross')->nullable();
            $table->date('published_at')->nullable();
            $table->date('expies_at')->nullable();
            $table->string('status', 32)->default('publish')->index();
            $table->string('apply_url')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('responses_count')->default(0);
            $table->text('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
