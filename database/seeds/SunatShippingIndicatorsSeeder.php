<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SunatShippingIndicatorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        $rows = [
            [
                'code' => 'IndicadorRetornoVehiculoEnvaseVacio',
                'name' => 'Retorno de vehículo con envase vacío',
                'sort_order' => 1
            ],
            [
                'code' => 'IndicadorRetornoVehiculoVacio',
                'name' => 'Retorno de vehículo vacío',
                'sort_order' => 2
            ],
            [
                'code' => 'IndicadorTrasladoVehiculoM1L',
                'name' => 'Traslado en vehículo M1 o L',
                'sort_order' => 3
            ],

        ];

        foreach ($rows as $r) {
            DB::table('sunat_shipping_indicators')->updateOrInsert(
                ['code' => $r['code']],
                [
                    'name' => $r['name'],
                    'is_active' => true,
                    'sort_order' => $r['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
