<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use Carbon\CarbonImmutable;

/** Regra 6: tem prazo futuro. */
final class RegraNoPrazo implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        return $t->prazo !== null
            ? new Status(TipoStatus::NoPrazo, 'Pendente', 'no prazo')
            : null;
    }
}
