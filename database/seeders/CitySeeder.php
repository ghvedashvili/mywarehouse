<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ცხრილის გასუფთავება (სურვილისამებრ)
        DB::table('cities')->delete();

        DB::table('cities')->insert([
            ['name' => 'თბილისი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ქუთაისი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ბათუმი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'რუსთავი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ზუგდიდი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'გორი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ფოთი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'თელავი', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ხაშური', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'სამტრედია', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}