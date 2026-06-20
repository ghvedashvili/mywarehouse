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
            $table->index('merged_id');
            $table->index(['status', 'order_type', 'is_primary']);
            $table->index('changed_to_order_id');
            $table->index('returned_purchase_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropIndex(['merged_id']);
            $table->dropIndex(['status', 'order_type', 'is_primary']);
            $table->dropIndex(['changed_to_order_id']);
            $table->dropIndex(['returned_purchase_id']);
        });
    }
};
