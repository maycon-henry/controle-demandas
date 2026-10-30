<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Regra 5: vence hoje. */
final class RegraVenceHoje implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        return $t->prazo !== null && Fuso::dia($t->prazo) === Fuso::dia($agora)
            ? new Status(TipoStatus::VenceHoje, 'Pendente', 'vence hoje')
            : null;
    }
}
