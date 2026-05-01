<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE finance_entries MODIFY COLUMN category ENUM('salary','utility','office','marketing','other','writeoff') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE finance_entries MODIFY COLUMN category ENUM('salary','utility','office','marketing','other') NOT NULL");
    }
};
