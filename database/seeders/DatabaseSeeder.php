<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. მომხმარებლები (Users)
        DB::table('users')->insert([
            [
                'name'     => 'Admin',
                'email'    => 'admin@mail.com',
                'password' => Hash::make('admin'), // ან შენი სასურველი პაროლი
                'role'     => 'admin',
                'created_at' => now()
            ],
            [
                'name'     => 'Admin',
                'email'    => 'ware1@mail.com',
                'password' => Hash::make('admin'), // ან შენი სასურველი პაროლი
                'role'     => 'warehouse_operator',
                'created_at' => now()
            ],
            [
                'name'     => 'Admin',
                'email'    => 'ware2@mail.com',
                'password' => Hash::make('admin'), // ან შენი სასურველი პაროლი
                'role'     => 'warehouse_operator',
                'created_at' => now()
            ],
            [
                'name'     => 'Admin',
                'email'    => 'sale@mail.com',
                'password' => Hash::make('admin'), // ან შენი სასურველი პაროლი
                'role'     => 'sale_operator',
                'created_at' => now()
            ]
        ]);

        // 2. კურიერები (Couriers)
        DB::table('couriers')->insert([
            [
                'name' => 'Standard Shipping',
                'international_price' => 30.00,
                'tbilisi_price' => 6.00,
                'region_price' => 9.00,
                'village_price' => 13.00,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // 3. დამოუკიდებელი ცხრილების შევსება
        $this->call([
            CitySeeder::class,         // ქალაქები
          //  OrderStatusSeeder::class,  // სტატუსები
            CategorySeeder::class,     // კატეგორიები
        ]);

        // 4. დამოკიდებული ცხრილების შევსება
        $this->call([
            CustomerSeeder::class,     // დამოკიდებულია City-ზე
            ProductSeeder::class,      // დამოკიდებულია Category-ზე
        ]);

        // 5. შეკვეთები (დამოკიდებულია ყველაფერზე)
        $this->call([
            ProductOrderSeeder::class,
        ]);
    }
}