<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->string('payment_comment', 500)->nullable()->after('fully_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropColumn('payment_comment');
        });
    }
};
