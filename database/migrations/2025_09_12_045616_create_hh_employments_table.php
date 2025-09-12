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
        Schema::create('hh_employments', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 64)->nullable()->unique();
            $table->string('name')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hh_employments');
    }
};
