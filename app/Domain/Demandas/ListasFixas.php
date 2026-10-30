<?php

namespace App\Domain\Demandas;

/** Listas fixas da seção 5.8, na ordem de exibição. */
final class ListasFixas
{
    public const CANAL_OUTRO = 'Outro';

    public const CANAIS = ['WhatsApp', 'E-mail', 'Ligação', 'Mensagem (SMS/Direct)', 'Presencial', 'Sistema/Portal', self::CANAL_OUTRO];

    public const SETORES = ['Fiscal', 'Contábil', 'Pessoal', 'Societário', 'Legalização', 'Parcelamentos', 'Reprocessamento de guias', 'Outro'];

    public const SETOR_PADRAO = 'Fiscal';

    /** Canal "Outro" com texto livre grava o texto; sem texto grava "Outro" (seção 5.3). */
    public static function normalizarCanal(?string $canal, ?string $canalOutro): string
    {
        if ($canal === self::CANAL_OUTRO || ! in_array($canal, self::CANAIS, true)) {
            $livre = trim((string) ($canal === self::CANAL_OUTRO ? $canalOutro : $canal));

            return $livre !== '' ? $livre : self::CANAL_OUTRO;
        }

        return $canal;
    }
}
