<?php

namespace App\Http\Controllers\Auth;

use App\Application\Usuarios\GestaoUsuarios;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\View\View;

/** Recuperação de senha, convite de primeiro acesso e troca de senha provisória (seção 4.3). */
class SenhaController extends Controller
{
    private const MSG_LINK_INVALIDO = 'Este link expirou ou já foi usado. Peça um novo.';

    public function esqueciForm(): View
    {
        return view('auth.esqueci');
    }

    public function esqueciEnviar(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], ['email.*' => 'Informe um e-mail válido.']);

        // Resposta igual exista ou não o e-mail, para não revelar quem tem cadastro.
        Password::broker('usuarios')->sendResetLink(['email' => $request->input('email'), 'ativo' => true]);

        return back()->with('aviso', 'Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha. Ele vale por 1 hora.');
    }

    public function redefinirForm(Request $request, string $token): View
    {
        return view('auth.nova-senha', [
            'token' => $token, 'email' => $request->query('email'),
            'acao' => route('senha.redefinir'), 'titulo' => 'Criar nova senha',
        ]);
    }

    public function redefinirSalvar(Request $request): RedirectResponse
    {
        return $this->definirPorToken($request, 'usuarios');
    }

    public function conviteForm(Request $request, string $token): View
    {
        return view('auth.nova-senha', [
            'token' => $token, 'email' => $request->query('email'),
            'acao' => route('convite.salvar'), 'titulo' => 'Bem-vindo! Crie sua senha',
        ]);
    }

    public function conviteSalvar(Request $request): RedirectResponse
    {
        return $this->definirPorToken($request, GestaoUsuarios::BROKER_CONVITES);
    }

    public function trocarForm(): View
    {
        return view('auth.trocar-senha');
    }

    public function trocarSalvar(Request $request): RedirectResponse
    {
        $request->validate(['senha' => ['required', 'confirmed', RegraSenha::defaults()]], $this->mensagensSenha());

        /** @var Usuario $usuario */
        $usuario = $request->user();
        $usuario->forceFill(['senha_hash' => $request->input('senha'), 'deve_trocar_senha' => false])->save();

        return redirect('/relatorio')->with('aviso', 'Senha alterada');
    }

    private function definirPorToken(Request $request, string $broker): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'senha' => ['required', 'confirmed', RegraSenha::defaults()],
        ], $this->mensagensSenha());

        $status = Password::broker($broker)->reset(
            [
                'email' => $request->input('email'), 'token' => $request->input('token'),
                'password' => $request->input('senha'), 'password_confirmation' => $request->input('senha_confirmation'),
            ],
            function (Usuario $usuario, string $senha) {
                $usuario->forceFill(['senha_hash' => Hash::make($senha), 'deve_trocar_senha' => false])->save();
                Auth::login($usuario);
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/relatorio')->with('aviso', 'Senha criada. Bem-vindo!')
            : back()->withInput($request->only('email'))->withErrors(['email' => self::MSG_LINK_INVALIDO]);
    }

    private function mensagensSenha(): array
    {
        return [
            'senha.required' => 'Informe a nova senha.',
            'senha.confirmed' => 'As senhas não conferem.',
            'senha.*' => 'A senha deve ter pelo menos 8 caracteres, com letras e números.',
        ];
    }
}
