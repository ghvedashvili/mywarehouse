<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'name' => 'Giorgi Beridze',
                'city_id' => 1,
                'address' => 'Tbilisi, Saburtalo',
                'email' => 'giorgi@example.com',
                'tel' => '599123456',
                'alternative_tel' => '577123456',
                'comment' => 'VIP customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nino Kapanadze',
                'city_id' => 2,
                'address' => 'Kutaisi, Rustaveli street',
                'email' => 'nino@example.com',
                'tel' => '599654321',
                'alternative_tel' => null,
                'comment' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lasha Gelashvili',
                'city_id' => null, // nullable გაქვს
                'address' => 'Batumi, Gonio',
                'email' => 'lasha@example.com',
                'tel' => '555111222',
                'alternative_tel' => '558333444',
                'comment' => 'Frequent buyer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}