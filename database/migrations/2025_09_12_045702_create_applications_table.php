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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained('resumes')->nullOnDelete();
            $table->string('status')->nullable()->index();
            $table->decimal('match_score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->string('external_id')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->string('hh_status')->nullable()->index();
            $table->timestamps();

            // Prevent duplicate applications to the same vacancy by the same user
            $table->unique(['user_id', 'vacancy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
