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
            $table->date('expires_at')->nullable();
            $table->string('status', 32)->default('publish')->index();
            $table->string('apply_url')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('responses_count')->default(0);
            $table->text('raw_data')->nullable();

            $table->string('company')->nullable()->index();
            $table->json('contact')->nullable();
            $table->string('language', 8)->nullable();
            $table->string('source_id')->nullable()->index();
            $table->string('source_message_id')->nullable()->index();
            $table->string('target_message_id')->nullable()->index();
            $table->unsignedBigInteger('target_msg_id')->nullable()->index();
            $table->string('signature', 64)->nullable();

            $table->timestamps();

            $table->unique(['source_id', 'source_message_id'], 'tg_vac_source_link_unique');
            $table->unique('signature', 'tg_vac_signature_unique');

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
