<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use Carbon\CarbonImmutable;

/** Regra 2: finalizada depois do prazo atual. */
final class RegraConcluidaComAtraso implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        return $t->fim !== null
            ? new Status(TipoStatus::ConcluidaComAtraso, 'Concluída', 'com atraso')
            : null;
    }
}
