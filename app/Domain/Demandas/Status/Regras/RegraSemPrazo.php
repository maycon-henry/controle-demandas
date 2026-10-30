<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use Carbon\CarbonImmutable;

/** Regra 7: sem prazo. Sempre se aplica, encerra a cadeia. */
final class RegraSemPrazo implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        return new Status(TipoStatus::SemPrazo, 'Pendente', 'sem prazo');
    }
}
