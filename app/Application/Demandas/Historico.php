<?php

namespace App\Application\Demandas;

use App\Domain\Shared\Relogio;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Carbon\CarbonInterface;

/** Grava eventos do histórico sempre com o autor (seção 4.4). */
final class Historico
{
    public function __construct(private readonly Relogio $relogio) {}

    public function registrar(Demanda $demanda, string $tipo, string $texto, Usuario $autor, ?CarbonInterface $em = null): Evento
    {
        return Evento::create([
            'demanda_id' => $demanda->id,
            'em' => $em ?? $this->relogio->agora(),
            'texto' => $texto,
            'tipo' => $tipo,
            'usuario_id' => $autor->id,
        ]);
    }
}
