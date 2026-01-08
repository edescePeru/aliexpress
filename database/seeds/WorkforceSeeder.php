<?php

use Illuminate\Database\Seeder;
use App\Workforce;
use App\UnitMeasure;

class WorkforceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UnitMeasure::create(['name' => 'UNIDAD', 'description' => 'UNIDAD']);

        Workforce::create([
            'description' => 'DESPACHO A AGENCIA',
            'unit_measure_id' => 1,
            'unit_price' => 0
        ]);
    }
}
