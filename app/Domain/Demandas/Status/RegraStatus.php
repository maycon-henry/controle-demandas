<?php

namespace App\Domain\Demandas\Status;

use App\Domain\Demandas\DemandaDados;
use Carbon\CarbonImmutable;

/** Uma regra da tabela 6.1: devolve o status quando se aplica, ou null para passar adiante. */
interface RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status;
}
