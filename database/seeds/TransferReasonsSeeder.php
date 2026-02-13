<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransferReasonsSeeder extends Seeder
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
            // Los más usados en tu UI MVP:
            ['code' => '01', 'name' => 'VENTA', 'sort_order' => 1],
            ['code' => '02', 'name' => 'COMPRA', 'sort_order' => 2],
            ['code' => '04', 'name' => 'TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA', 'sort_order' => 3],
            ['code' => '14', 'name' => 'VENTA SUJETA A CONFIRMACIÓN DEL COMPRADOR', 'sort_order' => 4],
            ['code' => '18', 'name' => 'TRASLADO EMISOR ITINERANTE CP', 'sort_order' => 5],
            ['code' => '08', 'name' => 'IMPORTACIÓN', 'sort_order' => 6],
            ['code' => '09', 'name' => 'EXPORTACIÓN', 'sort_order' => 7],
            ['code' => '05', 'name' => 'CONSIGNACION', 'sort_order' => 8],
            ['code' => '17', 'name' => 'TRASLADO DE BIENES PARA TRANSFORMACION', 'sort_order' => 9],
            ['code' => '03', 'name' => 'VENTA CON ENTREGA A TERCEROS', 'sort_order' => 10],
            ['code' => '06', 'name' => 'DEVOLUCION', 'sort_order' => 11],
            ['code' => '07', 'name' => 'RECOJO DE BIENES TRANSFORMADOS', 'sort_order' => 12],
            ['code' => '13', 'name' => 'OTROS', 'sort_order' => 13],
        ];

        foreach ($rows as $r) {
            DB::table('transfer_reasons')->updateOrInsert(
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
