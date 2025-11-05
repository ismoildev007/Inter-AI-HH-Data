<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optional: keep the latest note for quick reference (full history is stored in admin_check_notes)
            if (!Schema::hasColumn('users', 'admin_check_verify_note')) {
                $table->text('admin_check_verify_note')->nullable()->after('admin_check_status');
            }
            if (!Schema::hasColumn('users', 'admin_check_reject_note')) {
                $table->text('admin_check_reject_note')->nullable()->after('admin_check_verify_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_check_verify_note')) {
                $table->dropColumn('admin_check_verify_note');
            }
            if (Schema::hasColumn('users', 'admin_check_reject_note')) {
                $table->dropColumn('admin_check_reject_note');
            }
        });
    }
};

