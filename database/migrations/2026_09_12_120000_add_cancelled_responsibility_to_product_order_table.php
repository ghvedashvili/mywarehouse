<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->unsignedInteger('cancelled_responsible_user_id')->nullable()->after('returned_purchase_id');
            $table->text('cancelled_comment')->nullable()->after('cancelled_responsible_user_id');
            $table->foreign('cancelled_responsible_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropForeign(['cancelled_responsible_user_id']);
            $table->dropColumn(['cancelled_responsible_user_id', 'cancelled_comment']);
        });
    }
};
