<?php

namespace App\Domain\Demandas\Status\Regras;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\RegraStatus;
use App\Domain\Demandas\Status\Status;
use App\Domain\Demandas\Status\TipoStatus;
use App\Domain\Shared\Formatador;
use Carbon\CarbonImmutable;

/** Regra 4: tem adiamento e prazo. */
final class RegraAdiada implements RegraStatus
{
    public function avaliar(DemandaDados $t, CarbonImmutable $agora): ?Status
    {
        if (! $t->foiAdiada() || $t->prazo === null) {
            return null;
        }

        $complemento = Formatador::hora($t->prazo).($t->primeiroHorario ? ', 1º horário' : '');

        return new Status(TipoStatus::Adiada, 'Adiada para '.Formatador::dataCurta($t->prazo), $complemento);
    }
}
