<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
 
{
    Schema::create('products', function (Blueprint $table) {
        $table->increments('id'); // აქაც increments

        // ვიყენებთ ჩვეულებრივ integer-ს, რადგან categories.id-ც ასეთია
        $table->integer('category_id')->unsigned();
        
        $table->foreign('category_id')
              ->references('id')
              ->on('categories')
              ->onDelete('cascade');

        $table->string('name');
        $table->decimal('price_usa', 8, 2);
        $table->decimal('price_geo', 8, 2);
        $table->string('image')->nullable();
        $table->timestamps();
    });
}
 
   

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
