<?php

namespace App\Domain\Demandas;

use Carbon\CarbonImmutable;

final class AcaoDados
{
    public function __construct(
        public readonly CarbonImmutable $em,
        public readonly string $texto,
        public readonly ?string $usuarioNome = null,
    ) {}
}
