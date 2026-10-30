<?php

namespace App\Domain\Demandas\Status;

/** Grupos usados em filtros e contadores (seção 6.1). */
enum GrupoStatus: string
{
    case Concluida = 'concluida';
    case Pendente = 'pendente';
    case Adiada = 'adiada';
}
