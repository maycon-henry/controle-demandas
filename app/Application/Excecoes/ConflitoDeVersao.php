<?php

namespace App\Application\Excecoes;

/** Outra pessoa gravou a demanda depois que ela foi aberta para edição (seção 10). */
final class ConflitoDeVersao extends RegraViolada
{
    public function __construct()
    {
        parent::__construct('Esta tarefa foi alterada por outra pessoa. Recarregamos os dados; confira e salve de novo.');
    }
}
