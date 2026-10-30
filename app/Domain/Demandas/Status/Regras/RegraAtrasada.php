<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use Carbon\CarbonImmutable;

/** Regra 3: prazo já passou. Vem antes da 4, então uma adiada vencida volta a ser atrasada. */
final class RegraAtrasada implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        return $t->prazo !== null && $t->prazo < $agora
            ? new Status(TipoStatus::Atrasada, 'Pendente', 'atrasada')
            : null;
    }
}
