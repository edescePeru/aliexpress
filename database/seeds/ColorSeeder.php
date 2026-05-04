<?php

use Illuminate\Database\Seeder;
use \App\Color;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $colors = [
            [
                'name' => 'Blanco',
                'code' => '#FFFFFF',
                'short_name' => 'BLA',
            ],
            [
                'name' => 'Coral',
                'code' => '#FF7F50',
                'short_name' => 'COR',
            ],
            [
                'name' => 'Negro',
                'code' => '#000000',
                'short_name' => 'NEG',
            ],
            [
                'name' => 'Rosado',
                'code' => '#FFC5D3',
                'short_name' => 'ROSD',
            ],
            [
                'name' => 'Verde',
                'code' => '#008000',
                'short_name' => 'VER',
            ],
        ];

        foreach ($colors as $color) {
            Color::updateOrCreate(
                ['name' => $color['name']],
                $color
            );
        }
    }
}
