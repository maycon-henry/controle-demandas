<?php

namespace App\Domain\Shared;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Todas as regras de "dia" usam America/Sao_Paulo, independente do fuso do servidor (seção 10).
 */
final class Fuso
{
    public const TZ = 'America/Sao_Paulo';

    /** dia(x): data AAAA-MM-DD de um timestamp no fuso de Brasília. */
    public static function dia(?CarbonInterface $momento): ?string
    {
        return $momento?->setTimezone(self::TZ)->format('Y-m-d');
    }

    public static function parse(CarbonInterface|string $valor): CarbonImmutable
    {
        if ($valor instanceof CarbonInterface) {
            return CarbonImmutable::instance($valor)->setTimezone(self::TZ);
        }

        return CarbonImmutable::parse($valor, self::TZ)->setTimezone(self::TZ);
    }

    public static function parseOuNulo(CarbonInterface|string|null $valor): ?CarbonImmutable
    {
        return $valor === null || $valor === '' ? null : self::parse($valor);
    }

    /** Valor no formato do campo datetime-local ("AAAA-MM-DDTHH:MM"). */
    public static function local(?CarbonInterface $momento): ?string
    {
        return $momento?->setTimezone(self::TZ)->format('Y-m-d\TH:i');
    }

    public static function somarDias(string $dia, int $dias): string
    {
        return CarbonImmutable::parse($dia, self::TZ)->addDays($dias)->format('Y-m-d');
    }

    /** Início do dia (00h00) em Brasília. */
    public static function inicioDoDia(string $dia): CarbonImmutable
    {
        return CarbonImmutable::parse($dia.' 00:00', self::TZ);
    }
}
