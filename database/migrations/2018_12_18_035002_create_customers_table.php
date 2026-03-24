<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('status', ['active', 'deleted'])->default('active');
            $table->string('name');
            $table->foreignId('city_id')
                  ->nullable()
                  ->constrained('cities')
                  ->onDelete('set null');
            $table->string('address');
            $table->string('email')->unique();
            $table->string('tel')->unique();
            $table->string('alternative_tel', 20)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}