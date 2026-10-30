<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** CNPJ com 14 dígitos e dígitos verificadores válidos (seção 5.2). */
class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::valido((string) $value)) {
            $fail('CNPJ inválido.');
        }
    }

    public static function valido(string $cnpj): bool
    {
        if (! preg_match('/^\d{14}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $tamanho) {
            $pesos = $tamanho === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $soma = 0;
            foreach ($pesos as $i => $peso) {
                $soma += (int) $cnpj[$i] * $peso;
            }
            $digito = $soma % 11 < 2 ? 0 : 11 - $soma % 11;

            if ((int) $cnpj[$tamanho] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
