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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->nullable()->index();
            $table->string('external_id', 64)->nullable();
            $table->string('name')->nullable()->index();
            $table->string('url')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            // Prevent duplicates coming from the same source
            $table->unique(['source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
