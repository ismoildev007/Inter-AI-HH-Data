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
        Schema::create('resume_analyzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resume_id')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('keywords')->nullable();
            $table->string('language')->nullable();
            $table->string('skills')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_analyzes');
    }
};
