<?php

namespace App\Domain\Demandas;

use Carbon\CarbonImmutable;

final class AdiamentoDados
{
    public function __construct(
        public readonly CarbonImmutable $de,
        public readonly CarbonImmutable $para,
        public readonly CarbonImmutable $em,
        public readonly ?string $motivo = null,
    ) {}
}
