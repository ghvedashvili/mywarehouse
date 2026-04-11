<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('period_month', 7);           // "2026-04"
            $table->string('user_role', 30);
            $table->integer('order_count')->default(0);  // ჩათვლილი ორდერები
            $table->integer('deduction_count')->default(0); // გამოქვითული
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->decimal('deduction_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->text('note')->nullable();
            $table->unsignedInteger('recorded_by');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_payments');
    }
}
