<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_policies', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('name');
            $table->decimal('sale_base_per_order', 8, 2)->nullable();
            $table->decimal('sale_bonus_percent',  8, 4)->nullable();
            $table->decimal('warehouse_per_order', 8, 2)->nullable();
            $table->decimal('fixed_salary',        10, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->default('2050-01-01');
            $table->timestamps();
        });

        $now = now()->toDateString();

        DB::table('salary_policies')->insert([
            [
                'role'                => 'sale_operator',
                'name'                => 'გამყიდველი — სტანდარტული',
                'sale_base_per_order' => 3.00,
                'sale_bonus_percent'  => 0.0100,
                'warehouse_per_order' => null,
                'fixed_salary'        => null,
                'effective_from'      => $now,
                'effective_to'        => '2050-01-01',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'role'                => 'warehouse_operator',
                'name'                => 'საწყობი — სტანდარტული',
                'sale_base_per_order' => null,
                'sale_bonus_percent'  => null,
                'warehouse_per_order' => 1.00,
                'fixed_salary'        => null,
                'effective_from'      => $now,
                'effective_to'        => '2050-01-01',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'role'                => 'staff',
                'name'                => 'სტაფი — სტანდარტული',
                'sale_base_per_order' => null,
                'sale_bonus_percent'  => null,
                'warehouse_per_order' => null,
                'fixed_salary'        => 0.00,
                'effective_from'      => $now,
                'effective_to'        => '2050-01-01',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'role'                => 'admin',
                'name'                => 'ადმინი — სტანდარტული',
                'sale_base_per_order' => null,
                'sale_bonus_percent'  => null,
                'warehouse_per_order' => null,
                'fixed_salary'        => 0.00,
                'effective_from'      => $now,
                'effective_to'        => '2050-01-01',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_policies');
    }
};
