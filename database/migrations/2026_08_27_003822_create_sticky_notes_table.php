<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sticky_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->integer('pos_x')->default(120);
            $table->integer('pos_y')->default(120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticky_notes');
    }
};
