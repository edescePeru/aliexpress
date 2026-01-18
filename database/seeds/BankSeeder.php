<?php

use Illuminate\Database\Seeder;
use App\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bank::create([
            'name' => 'Banco de Crédito del Perú',
            'short_name' => 'BCP',
            'image' => 'bcp.png'
        ]);

        Bank::create([
            'name' => 'BBVA Continental',
            'short_name' => 'BBVA CONTINENTAL',
            'image' => 'bbva.jpg'
        ]);

        Bank::create([
            'name' => 'Banco Scotiabank',
            'short_name' => 'SCOTIABANK',
            'image' => 'scotiabank.png'
        ]);

        Bank::create([
            'name' => 'Banco Interbank',
            'short_name' => 'INTERBANK',
            'image' => 'interbank.png'
        ]);
    }
}
