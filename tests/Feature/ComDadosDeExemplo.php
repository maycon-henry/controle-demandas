<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\Usuario;
use Database\Seeders\DemonstracaoSeeder;

/** Carrega a equipe e as demandas do Apêndice A, com o relógio em 28/10/2026 19h30. */
trait ComDadosDeExemplo
{
    protected function carregarExemplo(): void
    {
        $this->fixarRelogio();
        $this->seed(DemonstracaoSeeder::class);
    }

    protected function gestor(): Usuario
    {
        return Usuario::query()->where('email', 'gestor@bitaraes.local')->firstOrFail();
    }

    protected function colaborador(string $email = 'ana@bitaraes.local'): Usuario
    {
        return Usuario::query()->where('email', $email)->firstOrFail();
    }

    protected function demanda(string $idLegado): Demanda
    {
        return Demanda::query()->where('id_legado', $idLegado)->firstOrFail();
    }
}
