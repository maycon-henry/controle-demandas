<?php

namespace App\Http\Controllers;

use App\Application\Excecoes\RegraViolada;
use App\Application\Usuarios\GestaoUsuarios;
use App\Domain\Demandas\ListasFixas;
use App\Domain\Shared\Texto;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Tela Usuários, só para gestor (seção 7.9). */
class UsuarioController extends Controller
{
    public function __construct(private readonly GestaoUsuarios $gestao) {}

    public function index(): View
    {
        $usuarios = Usuario::query()->get()
            ->sort(fn ($a, $b) => [! $a->ativo, Texto::chave($a->nome)] <=> [! $b->ativo, Texto::chave($b->nome)]);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new Usuario(['perfil' => Usuario::PERFIL_COLABORADOR]), 'setores' => ListasFixas::SETORES]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $this->gestao->criar($request->dados(), $request->senhaProvisoria());

        return redirect()->route('usuarios.index')->with('aviso', $request->senhaProvisoria()
            ? 'Usuário cadastrado. Ele deverá trocar a senha provisória no primeiro acesso.'
            : 'Usuário cadastrado. Convite enviado por e-mail.');
    }

    public function edit(Usuario $usuario): View
    {
        return view('usuarios.form', ['usuario' => $usuario, 'setores' => ListasFixas::SETORES]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        try {
            $this->gestao->atualizar($usuario, $request->dados(), $request->senhaProvisoria());
        } catch (RegraViolada $e) {
            return back()->withInput()->withErrors(['perfil' => $e->getMessage()]);
        }

        return redirect()->route('usuarios.index')->with('aviso', 'Usuário atualizado');
    }

    public function alternarAtivo(Usuario $usuario): RedirectResponse
    {
        try {
            $this->gestao->alternarAtivo($usuario);
        } catch (RegraViolada $e) {
            return back()->withErrors(['situacao' => $e->getMessage()]);
        }

        return back()->with('aviso', $usuario->ativo ? 'Usuário reativado' : 'Usuário desativado');
    }

    public function reenviarConvite(Usuario $usuario): RedirectResponse
    {
        $this->gestao->enviarConvite($usuario);

        return back()->with('aviso', 'Convite reenviado para '.$usuario->email);
    }
}
