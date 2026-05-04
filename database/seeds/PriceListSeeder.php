<?php

use Illuminate\Database\Seeder;
use \App\PriceList;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $priceLists = [
            [
                'name' => 'Precio Tienda',
                'currency' => 'PEN',
                'is_default' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Precio Mayorista',
                'currency' => 'PEN',
                'is_default' => 0,
                'is_active' => 1,
            ],
            [
                'name' => 'Precio USD',
                'currency' => 'USD',
                'is_default' => 0,
                'is_active' => 1,
            ],
        ];

        foreach ($priceLists as $list) {

            PriceList::updateOrCreate(
                ['name' => $list['name']], // 🔑 clave única lógica
                $list
            );
        }
    }
}
