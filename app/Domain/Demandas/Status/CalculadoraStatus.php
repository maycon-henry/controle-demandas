<?php

namespace App\Domain\Demandas\Status;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\Regras\RegraAdiada;
use App\Domain\Demandas\Status\Regras\RegraAtrasada;
use App\Domain\Demandas\Status\Regras\RegraConcluidaComAtraso;
use App\Domain\Demandas\Status\Regras\RegraConcluidaNoPrazo;
use App\Domain\Demandas\Status\Regras\RegraNoPrazo;
use App\Domain\Demandas\Status\Regras\RegraSemPrazo;
use App\Domain\Demandas\Status\Regras\RegraVenceHoje;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * Calcula o status percorrendo as regras da seção 6.1 em ordem (cadeia de responsabilidade):
 * a primeira que se aplica decide. O status nunca é gravado no banco.
 */
final class CalculadoraStatus
{
    /** @var list<RegraStatus> */
    private readonly array $regras;

    /** @param  list<RegraStatus>|null  $regras */
    public function __construct(?array $regras = null)
    {
        $this->regras = $regras ?? [
            new RegraConcluidaNoPrazo,
            new RegraConcluidaComAtraso,
            new RegraAtrasada,
            new RegraAdiada,
            new RegraVenceHoje,
            new RegraNoPrazo,
            new RegraSemPrazo,
        ];
    }

    public function calcular(DemandaDados $t, CarbonImmutable $agora): Status
    {
        foreach ($this->regras as $regra) {
            if ($status = $regra->avaliar($t, $agora)) {
                return $status;
            }
        }

        throw new LogicException('Nenhuma regra de status se aplicou.');
    }
}
