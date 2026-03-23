<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // მომხმარებლების შევსება
        DB::table('users')->insert([
            [
                'name'          => 'Admin',
                'email'         => 'admin@mail.com',
                'password'      => Hash::make('codeastro.com'),
                'created_at'    => now(),
                'role'          => 'admin'
            ],
            [
                'name'          => 'Staff',
                'email'         => 'staff@mail.com',
                'password'      => Hash::make('codeastro.com'),
                'created_at'    => now(),
                'role'          => 'staff'
            ],
        ]);

        DB::table('couriers')->insert([
            [
                'name'                  => 'Standard Shipping',
                'international_price'   => 30.00,
                'tbilisi_price'         => 6.00,
                'region_price'          => 15.00,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]
        ]);
        
        // გამოიძახე CitySeeder აქ
        $this->call([
            CitySeeder::class,
            CustomerSeeder::class,
        ]);
    }
}