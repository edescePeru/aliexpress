<?php

use Illuminate\Database\Seeder;
use \App\PercentageWorker;

class PercentageWorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PercentageWorker::create([
            'name' => 'assign_family',
            'value' => 10,
        ]);

        PercentageWorker::create([
            'name' => 'essalud',
            'value' => 9,
        ]);

        PercentageWorker::create([
            'name' => 'rmv',
            'value' => 1025,
        ]);

    }
}
