<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Login por e-mail e senha (seção 4.3). */
class LoginController extends Controller
{
    private const TENTATIVAS = 5;

    private const BLOQUEIO_SEGUNDOS = 15 * 60;

    private const ERRO_GENERICO = 'E-mail ou senha incorretos.';

    public function mostrar(): View
    {
        return view('auth.login');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $credenciais = $request->validate(
            ['email' => ['required', 'email'], 'senha' => ['required', 'string']],
            ['email.required' => 'Informe o e-mail.', 'email.email' => 'Informe um e-mail válido.', 'senha.required' => 'Informe a senha.'],
        );

        $chave = 'login:'.mb_strtolower($credenciais['email']);

        if (RateLimiter::tooManyAttempts($chave, self::TENTATIVAS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($chave) / 60);

            return $this->falha("Muitas tentativas erradas. Tente de novo em $minutos minuto(s).");
        }

        $ok = Auth::attempt(
            ['email' => $credenciais['email'], 'password' => $credenciais['senha'], 'ativo' => true],
            $request->boolean('manter_conectado'),
        );

        if (! $ok) {
            RateLimiter::hit($chave, self::BLOQUEIO_SEGUNDOS);

            return $this->falha(self::ERRO_GENERICO);
        }

        RateLimiter::clear($chave);
        $request->session()->regenerate();

        return redirect()->intended('/relatorio');
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function falha(string $mensagem): RedirectResponse
    {
        return back()->withInput(request()->only('email', 'manter_conectado'))->withErrors(['email' => $mensagem]);
    }
}
