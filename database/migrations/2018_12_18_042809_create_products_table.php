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
            $table->increments('id');

            // საგარეო გასაღები კატეგორიისთვის
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');

            // ახალი ველები
            $table->string('product_code')->unique(); // პროდუქტის კოდი
            $table->string('name');
            $table->decimal('price_usa', 8, 2);
            $table->decimal('price_geo', 8, 2);
            $table->string('image')->nullable();
            
            // სტატუსები: 1 = აქტიური/ხელმისაწვდომი, 0 = არააქტიური/არ არის
            $table->boolean('product_status')->default(1); 
            $table->boolean('in_warehouse')->default(1);

            // ზომები: შევინახავთ მძიმით გამოყოფილ ტექსტად (მაგ: "S,M,XL")
            $table->string('sizes')->nullable();

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