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
    Schema::create('sizes', function (Blueprint $table) {
        $table->increments('id');
        // ინდექსირებული ველი კატეგორიასთან დასაკავშირებლად
        $table->unsignedInteger('category_id'); 
        // თავად ზომის დასახელება (მაგ: S, 42, 100ml)
        $table->string('name'); 
        $table->timestamps();

        // უცხო გასაღები (Foreign Key): თუ კატეგორია წაიშლება, მისი ზომებიც წაიშალოს
        $table->foreign('category_id')
              ->references('id')
              ->on('categories')
              ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
