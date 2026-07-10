<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_usa_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('order_number')->nullable();
            $table->string('order_type', 20)->nullable();
            $table->unsignedTinyInteger('status_id')->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('trigger', 50);
            $table->text('trace')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_usa_audit_logs');
    }
};
