<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ფეხსაცმელი', 'sizes' => '38-45', 'color' => 'Black, White, Brown'],
            ['name' => 'აქსესუარები', 'sizes' => null, 'color' => 'Gold, Silver'],
            ['name' => 'ზედატანები', 'sizes' => 'S, M, L, XL', 'color' => 'Red, Blue, Green'],
            ['name' => 'შარვლები', 'sizes' => '30, 32, 34, 36', 'color' => 'Denim, Black'],
            ['name' => 'ქუდები', 'sizes' => 'Standard', 'color' => 'Various'],
            ['name' => 'ჩანთები', 'sizes' => null, 'color' => 'Leather, Black'],
            ['name' => 'საათები', 'sizes' => null, 'color' => 'Steel, Gold'],
            ['name' => 'სპორტული ფორმა', 'sizes' => 'S, M, L', 'color' => 'White, Blue'],
            ['name' => 'ქურთუკები', 'sizes' => 'M, L, XL', 'color' => 'Black, Navy'],
            ['name' => 'საცვლები', 'sizes' => 'S, M, L', 'color' => 'Basic Colors'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}