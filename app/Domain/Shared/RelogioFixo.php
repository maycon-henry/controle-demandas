<?php

namespace App\Domain\Shared;

use Carbon\CarbonImmutable;

final class RelogioFixo implements Relogio
{
    private CarbonImmutable $momento;

    public function __construct(CarbonImmutable|string $momento)
    {
        $this->momento = Fuso::parse($momento);
    }

    public function agora(): CarbonImmutable
    {
        return $this->momento;
    }
}
