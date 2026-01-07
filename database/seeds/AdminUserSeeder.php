<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\User;
use App\Worker;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@venti360.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin12345*'),
            ]
        );

        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        Worker::create([
            'first_name' => 'Admin',
            'last_name' => 'Master',
            'email' => 'admin@venti360.com',
            'user_id' => $user->id,
            'enable' => 1,
        ]);
    }
}
