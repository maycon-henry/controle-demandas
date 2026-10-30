<?php

return [

    'nome' => 'BITARÃES ASSESSORIA E CONSULTORIA CONTÁBIL',

    // Só fora de produção: fixa o "agora" (ex.: "2026-10-28 19:30") para demonstrações e conferência.
    'relogio_fixo' => env('RELOGIO_FIXO'),

    // Primeiro gestor criado na instalação (comando usuarios:gestor-inicial).
    'gestor_inicial' => [
        'nome' => env('GESTOR_NOME'),
        'email' => env('GESTOR_EMAIL'),
        'senha' => env('GESTOR_SENHA'),
    ],

];
