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
    Schema::create('couriers', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name'); // კურიერის სერვისის სახელი (მაგ: "Standard", "Express")
        
        // ტარიფები სხვადასხვა მიმართულებისთვის
        $table->decimal('international_price', 10, 2)->default(30.00);
        $table->decimal('tbilisi_price', 10, 2)->default(6.00);
        $table->decimal('region_price', 10, 2)->default(15.00);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
