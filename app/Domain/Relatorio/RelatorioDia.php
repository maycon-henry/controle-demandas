<?php

namespace App\Domain\Relatorio;

use App\Domain\Demandas\DemandaDados;

/** Resultado do relatório de um dia: as seções da tabela 6.2 e os indicadores do topo. */
final class RelatorioDia
{
    /**
     * @param  list<DemandaDados>  $feitas
     * @param  list<DemandaDados>  $emAndamento
     * @param  list<DemandaDados>  $ficouParaDepois
     * @param  list<DemandaDados>  $primeiroHorario
     * @param  list<DemandaDados>  $demaisAmanha
     * @param  list<DemandaDados>  $novas
     * @param  list<DemandaDados>  $atrasadasAnteriores
     * @param  list<DemandaDados>  $adiadasNoDia
     */
    public function __construct(
        public readonly string $dia,
        public readonly string $amanha,
        public readonly bool $ehHoje,
        public readonly ?string $responsavelId,
        public readonly array $feitas,
        public readonly array $emAndamento,
        public readonly array $ficouParaDepois,
        public readonly array $primeiroHorario,
        public readonly array $demaisAmanha,
        public readonly array $novas,
        public readonly array $atrasadasAnteriores,
        public readonly array $adiadasNoDia,
        public readonly int $previstas,
        public readonly int $cumpridas,
    ) {}

    public function filtrado(): bool
    {
        return $this->responsavelId !== null;
    }

    /** Percentual "Cumprido do previsto"; null quando nada estava previsto (exibe "–"). */
    public function cumpridoDoPrevisto(): ?int
    {
        return $this->previstas === 0 ? null : (int) round(100 * $this->cumpridas / $this->previstas);
    }

    public function cumpridoTexto(): string
    {
        $pct = $this->cumpridoDoPrevisto();

        return $pct === null ? '–' : $pct.'%';
    }

    /** @return list<DemandaDados> lista "amanhã" completa (primeiro horário + demais) */
    public function previstasAmanha(): array
    {
        return [...$this->primeiroHorario, ...$this->demaisAmanha];
    }
}
