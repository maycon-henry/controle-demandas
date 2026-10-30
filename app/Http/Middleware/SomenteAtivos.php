<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Usuário desativado perde o acesso imediatamente, mesmo com sessão aberta (seção 4.3). */
class SomenteAtivos
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            throw new AuthenticationException;
        }

        return $next($request);
    }
}
