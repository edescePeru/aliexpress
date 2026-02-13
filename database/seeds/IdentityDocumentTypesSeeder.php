<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdentityDocumentTypesSeeder extends Seeder
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
            ['code' => '1', 'name' => 'DNI - DOC. NACIONAL DE IDENTIDAD', 'sort_order' => 1],
            ['code' => '4', 'name' => 'CARNET DE EXTRANJERÍA', 'sort_order' => 2],
            ['code' => '7', 'name' => 'PASAPORTE', 'sort_order' => 3],
            ['code' => 'A', 'name' => 'CÉDULA DIPLOMATICA DE IDENTIDAD', 'sort_order' => 4],
            ['code' => 'G', 'name' => 'SALVOCONDUCTO', 'sort_order' => 5],
            ['code' => 'B', 'name' => 'DOC. IDENT. PAIS. RESIDENCIA - NO.D', 'sort_order' => 6],
            ['code' => 'C', 'name' => 'Tax Identification Number - TIN – Doc Trib PP.NN', 'sort_order' => 7],
            ['code' => 'D', 'name' => 'Identification Number - IN – Doc Trib PP.JJ', 'sort_order' => 8],
        ];

        foreach ($rows as $r) {
            DB::table('identity_document_types')->updateOrInsert(
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
