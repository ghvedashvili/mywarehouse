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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('customer_link_set_at')->nullable()->after('customer_linked_from');
        });

        // უკვე დაკავშირებული თანამშრომლებისთვის — updated_at საუკეთესო
        // მიახლოებაა იმისა, როდის მოხდა რეალურად დაკავშირება (customer_id
        // მხოლოდ ხელით, UserController::updateCustomerLink-დან იცვლება).
        DB::table('users')
            ->whereNotNull('customer_id')
            ->whereNull('customer_link_set_at')
            ->update(['customer_link_set_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('customer_link_set_at');
        });
    }
};
