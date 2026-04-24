<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->decimal('courier_refund', 10, 2)->default(0)->after('courier_price_village');
        });
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropColumn('courier_refund');
        });
    }
};
