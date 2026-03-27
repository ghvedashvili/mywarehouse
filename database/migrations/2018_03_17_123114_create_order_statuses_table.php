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
        ['name' => 'ახალი', 'color' => 'warning'],
        ['name' => 'გზაში', 'color' => 'primary'],
        ['name' => 'საწყობში', 'color' => 'secondary'],
        ['name' => 'კურიერთან', 'color' => 'success'],
        ['name' => 'დასრულებული', 'color' => 'dark'],
        ['name' => 'გაუქმებული', 'color' => 'danger'],
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
