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
    Schema::create('order_statuses', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name'); // მაგ: "Pending", "Success", "Returned"
        $table->string('color')->default('primary'); // AdminLTE-ს ფერებისთვის (success, danger, info)
        $table->timestamps();
    });

    // საწყისი მონაცემები, რომ ცარიელი არ იყოს
    DB::table('order_statuses')->insert([
        ['name' => 'Pending', 'color' => 'warning'],
        ['name' => 'Paid & Ready', 'color' => 'success'],
        ['name' => 'Sent to Courier', 'color' => 'info'],
        ['name' => 'Cancelled', 'color' => 'danger'],
    ]);
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
