<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (! Schema::hasColumn('resumes', 'first_name')) {
                $table->string('first_name')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('resumes', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('resumes', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('resumes', 'phone')) {
                $table->string('phone')->nullable()->after('contact_email');
            }

            if (! Schema::hasColumn('resumes', 'city')) {
                $table->string('city')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('resumes', 'country')) {
                $table->string('country')->nullable()->after('city');
            }

            if (! Schema::hasColumn('resumes', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('resumes', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable()->after('country');
            }

            if (! Schema::hasColumn('resumes', 'github_url')) {
                $table->string('github_url')->nullable()->after('linkedin_url');
            }

            if (! Schema::hasColumn('resumes', 'portfolio_url')) {
                $table->string('portfolio_url')->nullable()->after('github_url');
            }

            if (! Schema::hasColumn('resumes', 'desired_position')) {
                $table->string('desired_position')->nullable()->after('title');
            }

            if (! Schema::hasColumn('resumes', 'desired_salary')) {
                $table->text('desired_salary')->nullable()->after('desired_position');
            }

            if (! Schema::hasColumn('resumes', 'citizenship')) {
                $table->string('citizenship')->nullable()->after('country');
            }

            if (! Schema::hasColumn('resumes', 'employment_types')) {
                $table->json('employment_types')->nullable()->after('citizenship');
            }

            if (! Schema::hasColumn('resumes', 'work_schedules')) {
                $table->json('work_schedules')->nullable()->after('employment_types');
            }

            if (! Schema::hasColumn('resumes', 'ready_to_relocate')) {
                $table->boolean('ready_to_relocate')->default(false)->after('work_schedules');
            }

            if (! Schema::hasColumn('resumes', 'ready_for_trips')) {
                $table->boolean('ready_for_trips')->default(false)->after('ready_to_relocate');
            }

            if (! Schema::hasColumn('resumes', 'professional_summary')) {
                $table->text('professional_summary')->nullable()->after('description');
            }

            if (! Schema::hasColumn('resumes', 'languages')) {
                $table->json('languages')->nullable()->after('professional_summary');
            }

            if (! Schema::hasColumn('resumes', 'certificates')) {
                $table->json('certificates')->nullable()->after('languages');
            }

            if (! Schema::hasColumn('resumes', 'translations')) {
                $table->json('translations')->nullable()->after('certificates');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (Schema::hasColumn('resumes', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('resumes', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('resumes', 'contact_email')) {
                $table->dropColumn('contact_email');
            }
            if (Schema::hasColumn('resumes', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('resumes', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('resumes', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('resumes', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
            if (Schema::hasColumn('resumes', 'linkedin_url')) {
                $table->dropColumn('linkedin_url');
            }
            if (Schema::hasColumn('resumes', 'github_url')) {
                $table->dropColumn('github_url');
            }
            if (Schema::hasColumn('resumes', 'portfolio_url')) {
                $table->dropColumn('portfolio_url');
            }
            if (Schema::hasColumn('resumes', 'desired_position')) {
                $table->dropColumn('desired_position');
            }
            if (Schema::hasColumn('resumes', 'desired_salary')) {
                $table->dropColumn('desired_salary');
            }
            if (Schema::hasColumn('resumes', 'citizenship')) {
                $table->dropColumn('citizenship');
            }
            if (Schema::hasColumn('resumes', 'employment_types')) {
                $table->dropColumn('employment_types');
            }
            if (Schema::hasColumn('resumes', 'work_schedules')) {
                $table->dropColumn('work_schedules');
            }
            if (Schema::hasColumn('resumes', 'ready_to_relocate')) {
                $table->dropColumn('ready_to_relocate');
            }
            if (Schema::hasColumn('resumes', 'ready_for_trips')) {
                $table->dropColumn('ready_for_trips');
            }
            if (Schema::hasColumn('resumes', 'professional_summary')) {
                $table->dropColumn('professional_summary');
            }
            if (Schema::hasColumn('resumes', 'languages')) {
                $table->dropColumn('languages');
            }
            if (Schema::hasColumn('resumes', 'certificates')) {
                $table->dropColumn('certificates');
            }
            if (Schema::hasColumn('resumes', 'translations')) {
                $table->dropColumn('translations');
            }
        });
    }
};

