<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use Carbon\CarbonImmutable;

/** Regra 1: finalizada sem prazo ou até o prazo atual. */
final class RegraConcluidaNoPrazo implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        if ($t->fim !== null && ($t->prazo === null || $t->fim <= $t->prazo)) {
            return new Status(TipoStatus::ConcluidaNoPrazo, 'Concluída', 'no prazo');
        }

        return null;
    }
}
