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
            $table->increments('id');
            
            // კავშირი პროდუქტთან და ზომა
            $table->integer('product_id')->unsigned();
            $table->string('product_size')->nullable(); 
            
            // კავშირი კლიენტთან
            $table->integer('customer_id')->unsigned();
            
            // კავშირი სტატუსთან
            $table->integer('status_id')->unsigned()->default(1);
            
            // ვინ გააფორმა შეკვეთა (User ID)
            $table->integer('user_id')->unsigned();
            
            // კურიერის ინფორმაცია (ID და ფასები decimal ფორმატში)
            $table->integer('courier_id')->unsigned()->default(1);
            $table->decimal('courier_price_international', 10, 2)->default(0);
            $table->decimal('courier_price_tbilisi', 10, 2)->default(0);
            $table->decimal('courier_price_region', 10, 2)->default(0);
            
            // ძირითადი ფასები და ფასდაკლება
            $table->decimal('price_usa', 10, 2)->default(0);
            $table->decimal('price_georgia', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0); 
            
            // გადახდების ბლოკი
            $table->decimal('paid_tbc', 10, 2)->default(0);
            $table->decimal('paid_bog', 10, 2)->default(0);
            $table->decimal('paid_lib', 10, 2)->default(0);
            $table->decimal('paid_cash', 10, 2)->default(0);
            
            // ტიპი, კომენტარი და თაიმსტემპები
            $table->enum('order_type', ['sale', 'change'])->default('sale');
            $table->text('comment')->nullable();
            $table->timestamps(); // აქედან ვიგებთ შექმნის თარიღს (created_at)

            // Foreign Keys (კავშირები)
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('order_statuses');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('courier_id')->references('id')->on('couriers');
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