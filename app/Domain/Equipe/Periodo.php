<?php

namespace App\Domain\Equipe;

use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Período da aba Equipe: últimos 7, 30 ou 90 dias (inclusive hoje) ou desde o início. */
enum Periodo: string
{
    case Dias7 = '7';
    case Dias30 = '30';
    case Dias90 = '90';
    case Tudo = 'tudo';

    public const PADRAO = self::Dias30;

    public static function deTexto(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::PADRAO;
    }

    public function rotulo(): string
    {
        return $this === self::Tudo ? 'Desde o início' : 'Últimos '.$this->value.' dias';
    }

    /** Dia inicial (AAAA-MM-DD) do período, ou null para "desde o início". */
    public function primeiroDia(CarbonImmutable $agora): ?string
    {
        return $this === self::Tudo ? null : Fuso::somarDias(Fuso::dia($agora), -((int) $this->value - 1));
    }

    public function contem(?string $dia, CarbonImmutable $agora): bool
    {
        if ($dia === null) {
            return false;
        }

        $inicio = $this->primeiroDia($agora);

        return ($inicio === null || $dia >= $inicio) && $dia <= Fuso::dia($agora);
    }
}
