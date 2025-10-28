<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only for PostgreSQL
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        if (!Schema::hasTable('vacancies')) {
            return;
        }

        // Enable pg_trgm for trigram index (no-op if already enabled)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Full-text index (simple dictionary) on title+description
        DB::statement(<<<SQL
            CREATE INDEX IF NOT EXISTS vacancies_ft_simple_idx
            ON vacancies
            USING GIN (
                to_tsvector('simple', coalesce(title,'') || ' ' || coalesce(description,''))
            )
        SQL);

        // Trigram index to speed up ILIKE fallback on combined text
        DB::statement(<<<SQL
            CREATE INDEX IF NOT EXISTS vacancies_trgm_idx
            ON vacancies
            USING GIN ((coalesce(title,'') || ' ' || coalesce(description,'')) gin_trgm_ops)
        SQL);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        if (!Schema::hasTable('vacancies')) {
            return;
        }

        // Drop indexes if they exist
        DB::statement('DROP INDEX IF EXISTS vacancies_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS vacancies_ft_simple_idx');
    }
};

