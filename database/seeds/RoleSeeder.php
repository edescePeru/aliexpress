<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*Role::create([
            'name' => 'admin',
            'description' => 'Administrador' // Administrador
        ]);*/

        Role::create([
            'name' => 'owner',
            'description' => 'Propietario' // Propietario
        ]);

        Role::create([
            'name' => 'principal',
            'description' => 'Gerencia' // Gerencia
        ]);

        Role::create([
            'name' => 'worker',
            'description' => 'Trabajador' // Trabajador
        ]);

        Role::create([
            'name' => 'logistic',
            'description' => 'Logistica' // Logisitca
        ]);

        Role::create([
            'name' => 'almacen',
            'description' => 'Almacenero' // Almacen
        ]);
    }
}
