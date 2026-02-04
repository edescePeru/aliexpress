<?php

use Illuminate\Database\Seeder;
use App\CashBox;
use App\CashBoxSubtype;

class CashBoxesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1) Crear cajas base (si no existen)
        $cash = CashBox::firstOrCreate(
            ['type' => 'cash', 'name' => 'Efectivo'],
            [
                'uses_subtypes' => false,
                'is_active' => true,
                'position' => 1,
                'currency' => 'PEN',
            ]
        );

        $bank = CashBox::firstOrCreate(
            ['type' => 'bank', 'name' => 'Bancario'],
            [
                'uses_subtypes' => true,
                'is_active' => true,
                'position' => 2,
                'currency' => 'PEN',
            ]
        );

        // 2) Subtypes globales (cash_box_id NULL)
        $defaults = [
            ['code' => 'yape',     'name' => 'Yape',          'position' => 1],
            ['code' => 'plin',     'name' => 'Plin',          'position' => 2],
            ['code' => 'pos',      'name' => 'POS',           'position' => 3],
            ['code' => 'transfer', 'name' => 'Transferencia', 'position' => 4],
        ];

        foreach ($defaults as $row) {
            CashBoxSubtype::firstOrCreate(
                ['code' => $row['code'], 'cash_box_id' => null],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'position' => $row['position'],
                ]
            );
        }
    }
}
