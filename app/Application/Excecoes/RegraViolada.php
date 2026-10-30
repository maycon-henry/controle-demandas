<?php

namespace App\Application\Excecoes;

use DomainException;

/** Operação recusada por regra de negócio. A mensagem é exibida ao usuário como está. */
class RegraViolada extends DomainException {}
