<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('status_change_log', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('order_id');        // product_Order.id = increments = unsigned int
        $table->unsignedInteger('user_id');        // users.id = bigIncrements
        $table->unsignedInteger('status_id_from')->nullable();   // order_statuses.id = increments
        $table->unsignedInteger('status_id_to');                 // order_statuses.id = increments
        $table->timestamp('changed_at')->useCurrent();

        $table->foreign('order_id')
              ->references('id')->on('product_Order')   // ზუსტად ასე — დიდი O
              ->onDelete('cascade');

        $table->foreign('user_id')
              ->references('id')->on('users')
              ->onDelete('cascade');

        $table->foreign('status_id_from')
              ->references('id')->on('order_statuses')
              ->onDelete('set null');

        $table->foreign('status_id_to')
              ->references('id')->on('order_statuses')
              ->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('status_change_log');
}
};
