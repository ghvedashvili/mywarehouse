<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            DB::table('products')->insert([
                'category_id' => 3,
                'product_code' => 'SKU-00' . $i,
                'name' => 'პროდუქტი ' . $i,
                'price_usa' =>0,
                'price_geo' => rand(30, 150),
                'product_status' => 1,
                'in_warehouse' => 1,
                'created_at' => now(),
            ]);
        }
    }
}