<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_logs', function (Blueprint $table) {
            $table->id();

            // FK-ების გარეშე — products ცხრილი შეიძლება increments() იყოს (int), არა bigIncrements
            $table->unsignedInteger('product_id');
            $table->string('product_size', 50)->nullable();

            $table->enum('action', [
                'purchase_in',       // purchase 2→3: შემოვიდა საწყობში
                'purchase_rollback', // purchase 3→2: უკან გავიდა საწყობიდან
                'sale_out',          // sale კურიერთან გადაცემა (status→4)
                'defect',            // წუნი (partial receive-ზე)
                'lost',              // დაკარგული (partial receive-ზე)
                'adjustment',        // რაოდენობის კორექცია (საწყობში ყოფნისას)
            ]);

            $table->integer('qty_change');             // +N ან -N
            $table->integer('qty_before');             // physical_qty ცვლილებამდე
            $table->integer('qty_after');              // physical_qty ცვლილების შემდეგ

            $table->string('reference_type', 50)->nullable(); // 'purchase_order' | 'sale_order' | 'defect'
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('note')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Index-ები სიჩქარისთვის
            $table->index('product_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_logs');
    }
};