<?php

namespace App\Infrastructure\Persistencia;

final class FiltrosLista
{
    public function __construct(
        public readonly string $busca = '',
        public readonly FiltroSituacao $situacao = FiltroSituacao::Todas,
        public readonly string $canal = '',
        public readonly string $setor = '',
        public readonly string $responsavel = '',
    ) {}

    public function vazio(): bool
    {
        return $this->busca === '' && $this->situacao === FiltroSituacao::Todas
            && $this->canal === '' && $this->setor === '' && $this->responsavel === '';
    }
}
