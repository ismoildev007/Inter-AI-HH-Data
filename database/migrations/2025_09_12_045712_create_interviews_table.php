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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            // Link to applications (nullable for safety), enforce referential integrity
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            // Optional external scheduling/meeting id (kept as-is, may be linked later)
            $table->unsignedBigInteger('scheduled_id')->nullable();
            // pending | ready | failed
            $table->string('status')->nullable();
            $table->string('external_ref')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
