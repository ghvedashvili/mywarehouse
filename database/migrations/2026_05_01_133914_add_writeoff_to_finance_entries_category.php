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
        Schema::table('finance_entries', function (Blueprint $table) {
            // ვიყენებთ string-ს, რადგან ის უნივერსალურია PostgreSQL-ისთვის და MySQL-ისთვის
            // change() მეთოდი განაახლებს არსებულ სვეტს
            $table->string('category')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->string('category')->change();
        });
    }
};