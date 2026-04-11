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
    Schema::create('warehouse', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('product_id')->unsigned();
        $table->string('size')->nullable(); // ზომა (მაგ: 42, L, XL)
        
        // ძირითადი სვეტები ლოგიკისთვის
        $table->integer('physical_qty')->default(0);  // რაც ფიზიკურად გაქვს თაროზე
        $table->integer('incoming_qty')->default(0);  // რაც ქარხნიდან მოდის (In Transit)
        $table->integer('reserved_qty')->default(0);  // რაც მომხმარებელმა უკვე იყიდა (Reserved)
         $table->unsignedInteger('defect_qty')->default(0);
            $table->unsignedInteger('lost_qty')->default(0);
        $table->timestamps();

        // კავშირი products ცხრილთან
        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        
        // უნიკალურობა, რომ ერთი პროდუქტის ერთი და იგივე ზომა ორჯერ არ ჩაიწეროს
        $table->unique(['product_id', 'size']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse');
    }
};
