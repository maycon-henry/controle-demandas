<?php

namespace App\Domain\Demandas;

use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Funções auxiliares de prazo da seção 6.2. */
final class AnalisePrazos
{
    /**
     * prazosDaDemanda(t): dias de todos os prazos que a demanda já teve.
     *
     * @return array<string, true> conjunto de datas AAAA-MM-DD
     */
    public function prazosDaDemanda(DemandaDados $t): array
    {
        $dias = [];

        foreach ($t->adiamentos as $adiamento) {
            $dias[Fuso::dia($adiamento->de)] = true;
        }

        if ($t->prazo !== null) {
            $dias[Fuso::dia($t->prazo)] = true;
        }

        return $dias;
    }

    public function tinhaPrazoNoDia(DemandaDados $t, string $dia): bool
    {
        return isset($this->prazosDaDemanda($t)[$dia]);
    }

    /** Estava prevista para o dia e não foi finalizada até o fim dele. */
    public function pendenteNoDia(DemandaDados $t, string $dia): bool
    {
        return $this->tinhaPrazoNoDia($t, $dia)
            && ($t->fim === null || Fuso::dia($t->fim) > $dia);
    }

    /** Para onde o prazo foi empurrado a partir do dia. */
    public function prazoDepois(DemandaDados $t, string $dia): ?CarbonImmutable
    {
        $ultimo = null;

        foreach ($t->adiamentos as $adiamento) {
            if (Fuso::dia($adiamento->de) === $dia) {
                $ultimo = $adiamento;
            }
        }

        return $ultimo?->para ?? $t->prazo;
    }

    /** Prazo original: o "de" do primeiro adiamento ou, sem adiamentos, o próprio prazo. */
    public function prazoOriginal(DemandaDados $t): ?CarbonImmutable
    {
        return $t->adiamentos[0]->de ?? $t->prazo;
    }
}
