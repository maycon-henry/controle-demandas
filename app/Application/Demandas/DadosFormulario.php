<?php

namespace App\Application\Demandas;

use App\Domain\Demandas\ListasFixas;
use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Campos do formulário "Nova tarefa / Editar tarefa" (seção 7.4), já validados. */
final class DadosFormulario
{
    /** @param  list<string>  $etapas */
    public function __construct(
        public readonly CarbonImmutable $entrada,
        public readonly string $cliente,
        public readonly string $descricao,
        public readonly string $canal,
        public readonly string $setor,
        public readonly ?string $responsavelId,
        public readonly ?CarbonImmutable $acordoEm,
        public readonly ?CarbonImmutable $prazo,
        public readonly bool $primeiroHorario,
        public readonly ?CarbonImmutable $fim,
        public readonly ?string $observacoes,
        public readonly array $etapas,
        public readonly ?string $conclusao,
    ) {}

    /** @param  array<string, mixed>  $v  dados validados do request */
    public static function deArray(array $v): self
    {
        $texto = fn (string $k) => ($s = trim((string) ($v[$k] ?? ''))) === '' ? null : $s;

        return new self(
            entrada: Fuso::parse($v['entrada']),
            cliente: trim((string) $v['cliente']),
            descricao: trim((string) $v['descricao']),
            canal: ListasFixas::normalizarCanal($v['canal'] ?? null, $v['canal_outro'] ?? null),
            setor: $v['setor'] ?? ListasFixas::SETOR_PADRAO,
            responsavelId: $texto('responsavel_id'),
            acordoEm: Fuso::parseOuNulo($v['acordo_em'] ?? null),
            prazo: Fuso::parseOuNulo($v['prazo'] ?? null),
            primeiroHorario: (bool) ($v['primeiro_horario'] ?? false),
            fim: Fuso::parseOuNulo($v['fim'] ?? null),
            observacoes: $texto('observacoes'),
            etapas: self::linhas((string) ($v['etapas'] ?? '')),
            conclusao: $texto('conclusao'),
        );
    }

    /** @return list<string> */
    private static function linhas(string $texto): array
    {
        $linhas = array_map('trim', preg_split('/\R/u', $texto));

        return array_values(array_filter($linhas, fn (string $l) => $l !== ''));
    }
}
