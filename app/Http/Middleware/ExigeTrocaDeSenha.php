<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Quem entrou com senha provisória precisa trocá-la antes de usar o sistema (seção 4.3). */
class ExigeTrocaDeSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->deve_trocar_senha) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Troque a senha provisória para continuar.'], 403)
                : redirect()->route('senha.trocar');
        }

        return $next($request);
    }
}
