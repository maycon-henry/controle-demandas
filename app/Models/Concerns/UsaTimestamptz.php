<?php

namespace App\Models\Concerns;

/**
 * Grava datas com o deslocamento de fuso explícito, para que colunas timestamptz
 * recebam o instante correto independente do fuso da sessão do banco.
 */
trait UsaTimestamptz
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:sP';
    }
}
