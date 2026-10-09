<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Cidades onde a empresa já atende — as únicas que entram ativas.
     */
    private const ACTIVE = [
        'Aracati', 'Aracoiaba', 'Assaré', 'Baturité', 'Campos Sales', 'Cariús', 'Iguatu', 'Morada Nova',
        'Nova Olinda', 'Orós', 'Santana do Cariri', 'Várzea Alegre',
    ];

    /**
     * Todos os municípios do Brasil (IBGE), agrupados por UF em data/cities.json
     * como [código IBGE, nome]. Cidades que já existem só recebem o código IBGE.
     */
    public function run(): void
    {
        $citiesByState = json_decode(file_get_contents(__DIR__.'/data/cities.json'), true, flags: JSON_THROW_ON_ERROR);
        $now = now();

        $rows = [];
        foreach ($citiesByState as $state => $cities) {
            foreach ($cities as [$ibgeCode, $name]) {
                $rows[] = ['ibge_code' => $ibgeCode, 'name' => $name, 'state' => $state, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                City::upsert($chunk, ['name', 'state'], ['ibge_code']);
            }

            City::where('state', 'CE')->whereIn('name', self::ACTIVE)->update(['active' => true]);
        });
    }
}
