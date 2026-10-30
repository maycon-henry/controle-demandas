<?php

namespace App\Domain\Equipe;

final class LinhaDesempenho
{
    public function __construct(
        public readonly ?string $responsavelId,
        public readonly string $nome,
        public readonly bool $ativo,
        public readonly int $recebidas,
        public readonly int $concluidas,
        public readonly ?int $noPrazoPct,
        public readonly int $adiamentos,
        public readonly ?float $tempoMedioSegundos,
        public readonly int $emAberto,
        public readonly int $atrasadas,
    ) {}
}
