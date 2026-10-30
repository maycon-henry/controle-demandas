<?php

namespace App\Application\Demandas;

use App\Models\Cliente;

/** Procura o cliente pelo nome (sem diferenciar maiúsculas) e o cria se não existir (seção 5.2). */
final class ResolvedorCliente
{
    public function resolver(string $nome): Cliente
    {
        $nome = trim(preg_replace('/\s+/u', ' ', $nome));

        return Cliente::query()->whereRaw('lower(nome) = lower(?)', [$nome])->first()
            ?? Cliente::create(['nome' => $nome]);
    }
}
