<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: drop the ENUM check constraint, convert column to plain varchar
            DB::statement('ALTER TABLE finance_entries DROP CONSTRAINT IF EXISTS finance_entries_category_check');
            DB::statement('ALTER TABLE finance_entries ALTER COLUMN category TYPE VARCHAR(50)');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE finance_entries MODIFY COLUMN category VARCHAR(50) NOT NULL");
        }
        // sqlite — no action needed (no check constraints)
    }

    public function down(): void
    {
        // intentionally left blank — reverting to a restrictive ENUM would break data
    }
};
