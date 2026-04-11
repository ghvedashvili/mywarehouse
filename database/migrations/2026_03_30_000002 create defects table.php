<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defects', function (Blueprint $table) {
            $table->id();

            // FK-ების გარეშე — product_orders და products შეიძლება increments() იყოს
            $table->unsignedBigInteger('purchase_order_id'); // product_orders.id — bigIncrements
            $table->unsignedInteger('product_id');           // products.id — increments (int)
            $table->string('product_size', 50)->nullable();

            $table->enum('type', ['defect', 'lost']);
            $table->unsignedInteger('qty');

            $table->text('note')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};