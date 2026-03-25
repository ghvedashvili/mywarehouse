<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'გიორგი ბერიძე', 'city_id' => 1, 'address' => 'საბურთალო, ცინცაძის 5', 'email' => 'giorgi@mail.ge', 'tel' => '599111222'],
            ['name' => 'ნინო კაპანაძე', 'city_id' => 2, 'address' => 'რუსთაველის გამზირი 12', 'email' => 'nino@mail.ge', 'tel' => '599333444'],
            ['name' => 'ლაშა გელაშვილი', 'city_id' => 3, 'address' => 'გონიოს დასახლება', 'email' => 'lasha@mail.ge', 'tel' => '555112233'],
            ['name' => 'მარიამ დათუაშვილი', 'city_id' => 4, 'address' => 'მეგობრობის ქუჩა 8', 'email' => 'mari@mail.ge', 'tel' => '577445566'],
            ['name' => 'დავით კვირკველია', 'city_id' => 5, 'address' => 'აღმაშენებლის 20', 'email' => 'dato@mail.ge', 'tel' => '591009988'],
            ['name' => 'ანა ტაბატაძე', 'city_id' => 6, 'address' => 'სტალინის გამზირი 5', 'email' => 'ana@mail.ge', 'tel' => '595123456'],
            ['name' => 'ირაკლი მახარაძე', 'city_id' => 7, 'address' => 'ნავსადგურის უბანი', 'email' => 'irakli@mail.ge', 'tel' => '558998877'],
            ['name' => 'ელენე ჯაფარიძე', 'city_id' => 8, 'address' => 'ერეკლე II-ის მოედანი', 'email' => 'elene@mail.ge', 'tel' => '593114477'],
            ['name' => 'ალექსანდრე მესხი', 'city_id' => 1, 'address' => 'ვაკე, ჭავჭავაძის 17', 'email' => 'sandro@mail.ge', 'tel' => '599001122'],
            ['name' => 'თამარ ჩხეიძე', 'city_id' => 10, 'address' => 'ცენტრალური ქუჩა 1', 'email' => 'tako@mail.ge', 'tel' => '574556677'],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->insert(array_merge($customer, [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}