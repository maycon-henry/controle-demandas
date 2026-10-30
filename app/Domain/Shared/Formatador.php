<?php

namespace App\Domain\Shared;

use Carbon\CarbonInterface;

/**
 * Formatos de data, hora e duração da seção 6.8. Implementado sem depender do ICU
 * para que o resultado seja idêntico em qualquer servidor.
 */
final class Formatador
{
    private const DIAS_SEMANA = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];

    private const MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    private const SEGUNDOS_POR_HORA = 3600;

    private const SEGUNDOS_POR_DIA = 86400;

    /** 28/10/26 */
    public static function dataCurta(?CarbonInterface $m): string
    {
        return $m ? self::noFuso($m)->format('d/m/y') : '';
    }

    /** 09h05 */
    public static function hora(?CarbonInterface $m): string
    {
        return $m ? self::noFuso($m)->format('H\hi') : '';
    }

    /** 28/10/26 09h05 */
    public static function dataHora(?CarbonInterface $m): string
    {
        return $m ? self::noFuso($m)->format('d/m/y H\hi') : '';
    }

    /** 28/10/26 09:05 */
    public static function csv(?CarbonInterface $m): string
    {
        return $m ? self::noFuso($m)->format('d/m/y H:i') : '';
    }

    /** quarta-feira, 28 de outubro de 2026 */
    public static function extenso(string $dia): string
    {
        $m = Fuso::inicioDoDia($dia);

        return sprintf('%s, %d de %s de %d', self::diaDaSemana($dia), $m->day, self::MESES[$m->month - 1], $m->year);
    }

    /** quinta-feira */
    public static function diaDaSemana(string $dia): string
    {
        return self::DIAS_SEMANA[Fuso::inicioDoDia($dia)->dayOfWeek];
    }

    /** 8h · 2,1 dias · 12 dias */
    public static function duracao(int|float|null $segundos): string
    {
        if ($segundos === null) {
            return '–';
        }

        if ($segundos < self::SEGUNDOS_POR_DIA) {
            return max(1, (int) round($segundos / self::SEGUNDOS_POR_HORA)).'h';
        }

        $dias = $segundos / self::SEGUNDOS_POR_DIA;

        if ($dias < 10) {
            return number_format($dias, 1, ',', '').' dias';
        }

        return ((int) round($dias)).' dias';
    }

    public static function primeiraMaiuscula(string $texto): string
    {
        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_substr($texto, 1);
    }

    private static function noFuso(CarbonInterface $m): CarbonInterface
    {
        return $m->copy()->setTimezone(Fuso::TZ);
    }
}
