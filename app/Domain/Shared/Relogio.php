<?php

namespace App\Domain\Shared;

use Carbon\CarbonImmutable;

/**
 * Fonte do "agora". Injetada em vez de chamar now() direto, para que as regras
 * dependentes do momento atual sejam testáveis (seção 14 fixa 28/10/2026 19h30).
 */
interface Relogio
{
    public function agora(): CarbonImmutable;
}
