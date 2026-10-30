<?php

namespace App\Domain\Shared;

use Illuminate\Support\Str;

final class Texto
{
    /** Chave de comparação sem acentos e sem diferenciar maiúsculas. */
    public static function chave(string $texto): string
    {
        return mb_strtolower(Str::ascii($texto));
    }

    public static function compararNomes(string $a, string $b): int
    {
        return strcmp(self::chave($a), self::chave($b));
    }

    /** "Carla Mendes" → "carla-mendes" */
    public static function slug(string $texto): string
    {
        return Str::slug($texto);
    }

    /** Reduz espaços múltiplos a um e limita o tamanho (texto do WhatsApp, seção 8.3). */
    public static function resumir(string $texto, int $limite): string
    {
        $limpo = trim(preg_replace('/\s+/u', ' ', $texto));

        return mb_strlen($limpo) > $limite ? rtrim(mb_substr($limpo, 0, $limite - 1)).'…' : $limpo;
    }
}
