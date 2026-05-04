<?php

use Illuminate\Database\Seeder;
use App\Area;
use App\Warehouse;
use App\Shelf;
use App\Level;
use App\Container;
use App\Position;
use App\Location;

class DataFirstPositionAlmacenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Area::create([
            'name' => 'TRUJ',
            'comment' => 'SEDE TRUJILLO'
        ]);

        Warehouse::create([
            'name' => 'GEN',
            'comment' => 'Almacén principal',
            'area_id' => 1,
            'is_default' => 1
        ]);

        Shelf::create([
            'name' => 'GEN',
            'comment' => 'Estante principal',
            'warehouse_id' => 1
        ]);

        Level::create([
            'name' => 'GEN',
            'comment' => 'Nivel principal',
            'shelf_id' => 1
        ]);

        Container::create([
            'name' => 'GEN',
            'comment' => 'Contenedor principal',
            'level_id' => 1
        ]);

        Position::create([
            'name' => 'GEN',
            'comment' => 'Posicion única',
            'container_id' => 1,
            'status' => 'active'
        ]);

        Location::create([
            'area_id' => 1,
            'warehouse_id' => 1,
            'shelf_id' => 1,
            'level_id' => 1,
            'container_id' => 1,
            'position_id' => 1,
            'description' => 'UBICACIÓN ÚNICA',
            'default' => 1
        ]);
    }
}
