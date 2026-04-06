<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', [
                'salary',       // ხელფასი
                'utility',      // კომუნალური
                'office',       // ოფისი/ქირა
                'marketing',    // მარკეტინგი
                'other',        // სხვა
            ]);
            $table->string('description')->nullable();   // თავისუფალი ტექსტი
            $table->decimal('amount', 12, 2);            // ₾
            $table->date('entry_date');                  // თარიღი
            $table->unsignedBigInteger('user_id');       // users.id
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
    }
};