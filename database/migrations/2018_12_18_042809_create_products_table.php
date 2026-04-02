<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('status', ['active', 'deleted'])->default('active');

          $table->integer('category_id')->unsigned()->nullable();
            $table->foreign('category_id')
      ->references('id')
      ->on('categories')
      ->onDelete('set null');

            $table->string('product_code')->unique();
            $table->string('name');
            $table->decimal('price_usa', 8, 2)->default(0);
            $table->decimal('price_geo', 8, 2);
            $table->string('image')->nullable();
            $table->boolean('product_status')->default(1);
            $table->boolean('in_warehouse')->default(1);
            $table->string('sizes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}