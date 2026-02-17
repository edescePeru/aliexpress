<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UbigeoSqlSeeder extends Seeder
{
    public function run()
    {
        $path = database_path('seeds/sql/ubigeo_peru_inei_2016.sql');

        if (!File::exists($path)) {
            throw new \Exception("No existe el archivo SQL: {$path}");
        }

        $sql = File::get($path);

        // OJO: DB::unprepared ejecuta todo el SQL tal cual
        DB::unprepared($sql);
    }
}
