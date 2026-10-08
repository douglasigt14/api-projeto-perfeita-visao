<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Cidades da agenda do protótipo.
     */
    public function run(): void
    {
        foreach (['Itapipoca', 'Itapajé', 'Tururu', 'Uruburetama', 'Amontada', 'Trairi'] as $name) {
            City::firstOrCreate(['name' => $name, 'state' => 'CE']);
        }
    }
}
