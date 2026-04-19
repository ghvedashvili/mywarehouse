<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductOrderTable extends Migration
{
    public function up()
    {
        Schema::create('product_Order', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_number', 20)->nullable()->unique();

            $table->enum('status', ['active', 'deleted'])->default('active');
$table->tinyInteger('sale_from')->default(0);
            $table->unsignedInteger('merged_id')->nullable();
            $table->boolean('is_primary')->default(0);
            $table->unsignedInteger('purchase_group_id')->nullable()->index();
            $table->unsignedInteger('original_qty')->nullable();

            $table->integer('product_id')->unsigned();
            $table->string('product_size')->nullable();
            $table->string('color')->nullable();
            $table->integer('quantity')->default(1);
$table->decimal('cost_price', 10, 2)->default(0);
$table->unsignedInteger('purchase_order_id')->nullable()->index();
$table->unsignedInteger('original_sale_id')->nullable()->index();
 $table->unsignedBigInteger('changed_to_order_id')->nullable();
 $table->unsignedBigInteger('returned_purchase_id')->nullable();
           $table->integer('customer_id')->unsigned()->nullable();
            $table->integer('status_id')->unsigned()->default(1);
            $table->integer('user_id')->unsigned();

            $table->integer('courier_id')->unsigned()->default(1);
            $table->decimal('courier_price_international', 10, 2)->default(0);
            $table->decimal('courier_price_tbilisi', 10, 2)->default(0);
            $table->decimal('courier_price_region', 10, 2)->default(0);
            $table->decimal('courier_price_village', 10, 2)->default(0);

            $table->decimal('price_usa', 10, 2)->default(0);
            $table->decimal('price_georgia', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);

            $table->decimal('paid_tbc', 10, 2)->default(0);
            $table->decimal('paid_bog', 10, 2)->default(0);
            $table->decimal('paid_lib', 10, 2)->default(0);
            $table->decimal('paid_cash', 10, 2)->default(0);

            $table->enum('order_type', ['sale', 'change', 'purchase'])->default('sale');
            $table->text('comment')->nullable();
            $table->string('order_address', 500)->nullable();
            $table->string('order_alt_tel', 50)->nullable();
            $table->unsignedBigInteger('order_city_id')->nullable();
            $table->timestamp('cancelled_at')->nullable(); // გაუქმების/დაბრუნების ზუსტი თარიღი
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('order_statuses');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('courier_id')->references('id')->on('couriers');

             
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_Order');
    }
}