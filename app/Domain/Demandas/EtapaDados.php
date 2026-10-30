<?php

namespace App\Domain\Demandas;

use Carbon\CarbonImmutable;

final class EtapaDados
{
    public function __construct(
        public readonly string $texto,
        public readonly bool $feita = false,
        public readonly ?CarbonImmutable $feitaEm = null,
        public readonly ?string $id = null,
    ) {}
}
