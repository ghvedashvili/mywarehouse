<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('product_Order', function (Blueprint $table) {
        $table->increments('id'); // ვიყენებთ increments-ს ერთიანობისთვის

        // პროდუქტზე მიბმა (უნდა იყოს integer და unsigned)
        $table->integer('product_id')->unsigned();
        $table->foreign('product_id')
              ->references('id')
              ->on('products')
              ->onDelete('cascade');

        // კლიენტზე მიბმა (უნდა იყოს integer და unsigned)
        $table->integer('customer_id')->unsigned();
        $table->foreign('customer_id')
              ->references('id')
              ->on('customers')
              ->onDelete('cascade');

        $table->integer('qty');
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
        Schema::dropIfExists('product_Order');
    }
}
