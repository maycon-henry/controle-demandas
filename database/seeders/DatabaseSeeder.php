<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Ambiente local: equipe fictícia e as dez demandas do Apêndice A. */
    public function run(): void
    {
        $this->call(DemonstracaoSeeder::class);
    }
}
