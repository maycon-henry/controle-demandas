<?php

namespace App\Domain\Shared;

use Carbon\CarbonImmutable;

final class RelogioSistema implements Relogio
{
    public function agora(): CarbonImmutable
    {
        return CarbonImmutable::now(Fuso::TZ)->startOfMinute();
    }
}
