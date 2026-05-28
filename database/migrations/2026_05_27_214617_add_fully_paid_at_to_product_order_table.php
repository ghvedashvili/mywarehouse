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
            $table->timestamp('fully_paid_at')->nullable()->after('paid_cash');
        });

        // Backfill: ყველა ორდერი სადაც sum(paid) >= price - discount → NOW()
        \DB::statement("
            UPDATE product_Order
            SET fully_paid_at = NOW()
            WHERE order_type IN ('sale', 'change')
              AND COALESCE(price_georgia, 0) > 0
              AND (
                  COALESCE(paid_tbc, 0) + COALESCE(paid_bog, 0)
                + COALESCE(paid_lib, 0) + COALESCE(paid_cash, 0)
              ) >= (COALESCE(price_georgia, 0) - COALESCE(discount, 0))
              AND fully_paid_at IS NULL
        ");

        // Backfill (deleted): production-ზე price_georgia=0 ყველა წაშლილ ორდერზე
        \DB::statement("
            UPDATE product_Order
            SET fully_paid_at = NOW()
            WHERE order_type IN ('sale', 'change')
              AND status = 'deleted'
              AND (
                  COALESCE(paid_tbc, 0) + COALESCE(paid_bog, 0)
                + COALESCE(paid_lib, 0) + COALESCE(paid_cash, 0)
              ) > 0
              AND fully_paid_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropColumn('fully_paid_at');
        });
    }
};
