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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->nullable()->index();
            $table->string('external_id', 64)->nullable();
            $table->string('name')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['parent_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
