<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->timestamp('cancelled_responsibility_assigned_at')->nullable()->after('cancelled_comment');
        });

        // უკვე მინიჭებული "პასუხისმგებელის" მქონე ძველი ჩანაწერებისთვის —
        // updated_at საუკეთესო მიახლოებაა იმისა, როდის მოხდა რეალურად მინიჭება
        // (ეს ველი მხოლოდ ხელით, updateReturnResponsibility-დან იცვლება).
        DB::table('product_Order')
            ->whereNotNull('cancelled_responsible_user_id')
            ->whereNull('cancelled_responsibility_assigned_at')
            ->update(['cancelled_responsibility_assigned_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_Order', function (Blueprint $table) {
            $table->dropColumn('cancelled_responsibility_assigned_at');
        });
    }
};
