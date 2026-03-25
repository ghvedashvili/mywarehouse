<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('products')->insert([
                'category_id' => $i,
                'product_code' => 'SKU-00' . $i,
                'name' => 'პროდუქტი ' . $i,
                'price_usa' => rand(10, 50),
                'price_geo' => rand(30, 150),
                'product_status' => 1,
                'in_warehouse' => 1,
                'created_at' => now(),
            ]);
        }
    }
}