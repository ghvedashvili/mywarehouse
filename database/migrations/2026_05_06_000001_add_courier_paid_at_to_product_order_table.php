<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->timestamp('courier_paid_at')->nullable()->after('courier_refund');
        });
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropColumn('courier_paid_at');
        });
    }
};
