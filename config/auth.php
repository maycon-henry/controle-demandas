<?php

use App\Models\Usuario;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'usuarios',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios',
            // "Manter conectado": 30 dias (seção 4.3). Sem ele, vale SESSION_LIFETIME (12 h).
            'remember' => 60 * 24 * 30,
        ],
    ],

    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model' => Usuario::class,
        ],
    ],

    'passwords' => [
        // Redefinição de senha: link de uso único válido por 1 hora.
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        // Convite de primeiro acesso: link de uso único válido por 7 dias.
        'convites' => [
            'provider' => 'usuarios',
            'table' => 'convite_tokens',
            'expire' => 60 * 24 * 7,
            'throttle' => 0,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
