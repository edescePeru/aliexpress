<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeightUnitsSeeder extends Seeder
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
            ['code' => 'KGM', 'name' => 'KILOGRAMO', 'sort_order' => 1],
            ['code' => 'TNE', 'name' => 'TONELADA (TONELADA MÉTRICA)', 'sort_order' => 2],
        ];

        foreach ($rows as $r) {
            DB::table('weight_units')->updateOrInsert(
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
