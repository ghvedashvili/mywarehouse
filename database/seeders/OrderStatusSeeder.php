<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['ახალი', 'მიღებული', 'მზადდება', 'გზაშია', 'კურიერთან', 'დაბრუნებული', 'გაცვლილი', 'გაუქმებული', 'დაბრუნებული', 'დაპაუზებული'];

        foreach ($statuses as $status) {
            DB::table('order_statuses')->insert([
                'name' => $status,
                'created_at' => now()
            ]);
        }
    }
}