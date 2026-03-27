<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductOrderSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            DB::table('product_Order')->insert([
                'product_id'    => rand(1, 10), // გვაქვს 10 პროდუქტი
                'customer_id'   => rand(1, 10), // გვაქვს 10 კლიენტი
                'status_id'     => rand(1, 6), // გვაქვს 10 სტატუსი
                'user_id'       => 1,           // Admin მომხმარებელი
                'courier_id'    => 1,           // Standard Shipping კურიერი
                
                'product_size'  => 'M',
                'color'         => 'Black',
                
                // ფასების სიმულაცია
                'price_usa'     => rand(20, 100),
                'price_georgia' => rand(60, 300),
                'discount'      => rand(0, 10),
                
                // კურიერის ფასები (შენი მიგრაციის მიხედვით)
                'courier_price_international' => 30.00,
                'courier_price_tbilisi'       => 6.00,
                'courier_price_region'        => 15.00,
                
                // გადახდის მეთოდების სიმულაცია (მხოლოდ ერთი იყოს შევსებული)
                'paid_cash'     => rand(0, 1) ? rand(50, 200) : 0,
                'paid_tbc'      => 0,
                'paid_bog'      => 0,
                
                'order_type'    => 'sale',
                'comment'       => 'სატესტო შეკვეთა #' . $i,
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}