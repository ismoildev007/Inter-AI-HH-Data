<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_vacancies', function (Blueprint $table) {
            $table->id();
            // Core normalized fields
            $table->string('title')->nullable()->index();
            $table->string('company')->nullable()->index();
            $table->json('contact')->nullable(); // phones[], telegram_usernames[]
            $table->longText('description')->nullable();
            $table->string('language', 8)->nullable();

            // Status & links
            $table->string('status', 20)->default('publish')->index();
            // Source channel username (with @) and source post link
            $table->string('source_id')->index();
            $table->string('source_message_id')->index();
            // Target post link (t.me/target_username/message_id)
            $table->string('target_message_id')->nullable()->index();

            // Cross-channel dedupe signature (deterministic hash)
            $table->string('signature', 64)->nullable();

            $table->timestamps();

            // Uniqueness constraints
            $table->unique(['source_id', 'source_message_id'], 'tg_vac_source_link_unique');
            $table->unique('signature', 'tg_vac_signature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_vacancies');
    }
};
