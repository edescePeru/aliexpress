<?php

use Illuminate\Database\Seeder;
use App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userA = User::create([
            'name' => 'Admin Master',
            'email' => 'admin@sermeind.com',
            'password' => bcrypt('$ermeind2021'),
            'image' => '1.jpg',
        ]);

        /*$userAl = User::create([
            'name' => 'Almacén',
            'email' => 'almacen@sermeind.com',
            'password' => bcrypt('$ermeind2021'),
            'image' => '2.jpg',
        ]);*/

        $userA->assignRole('admin');
        //$userAl->assignRole('almacen');
    }
}
