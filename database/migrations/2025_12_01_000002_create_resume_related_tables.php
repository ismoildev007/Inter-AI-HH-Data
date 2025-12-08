<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resume_experiences')) {
            Schema::create('resume_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
                $table->string('position')->nullable();
                $table->string('company')->nullable();
                $table->string('location')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('resume_educations')) {
            Schema::create('resume_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
                $table->string('degree')->nullable();
                $table->string('institution')->nullable();
                $table->string('location')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('extra_info')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('resume_skills')) {
            Schema::create('resume_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
                $table->string('name');
                $table->string('level');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_skills');
        Schema::dropIfExists('resume_educations');
        Schema::dropIfExists('resume_experiences');
    }
};

