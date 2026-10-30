<?php

namespace App\Domain\Demandas;

use Carbon\CarbonImmutable;

/**
 * Retrato imutável de uma demanda, usado por todas as regras de negócio.
 * Não conhece o banco: é montado pelo repositório e pode ser criado à mão nos testes.
 */
final class DemandaDados
{
    /** @var list<AdiamentoDados> em ordem cronológica de registro */
    public readonly array $adiamentos;

    /** @var list<AcaoDados> em ordem crescente de data */
    public readonly array $acoes;

    /**
     * @param  list<AdiamentoDados>  $adiamentos
     * @param  list<EtapaDados>  $etapas
     * @param  list<AcaoDados>  $acoes
     */
    public function __construct(
        public readonly string $id,
        public readonly CarbonImmutable $entrada,
        public readonly string $cliente = '',
        public readonly string $descricao = '',
        public readonly string $canal = 'WhatsApp',
        public readonly string $setor = 'Fiscal',
        public readonly ?string $responsavelId = null,
        public readonly ?string $responsavelNome = null,
        public readonly ?CarbonImmutable $acordoEm = null,
        public readonly ?CarbonImmutable $prazo = null,
        public readonly bool $primeiroHorario = false,
        public readonly ?CarbonImmutable $fim = null,
        public readonly ?string $conclusao = null,
        public readonly ?string $observacoes = null,
        array $adiamentos = [],
        public readonly array $etapas = [],
        array $acoes = [],
    ) {
        usort($adiamentos, fn (AdiamentoDados $a, AdiamentoDados $b) => $a->em <=> $b->em);
        usort($acoes, fn (AcaoDados $a, AcaoDados $b) => $a->em <=> $b->em);
        $this->adiamentos = $adiamentos;
        $this->acoes = $acoes;
    }

    public function finalizada(): bool
    {
        return $this->fim !== null;
    }

    public function foiAdiada(): bool
    {
        return $this->adiamentos !== [];
    }

    public function etapasFeitas(): int
    {
        return count(array_filter($this->etapas, fn (EtapaDados $e) => $e->feita));
    }

    public function nomeResponsavel(): string
    {
        return $this->responsavelNome ?? '(sem responsável)';
    }
}
