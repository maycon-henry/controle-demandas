<?php

namespace App\Application\Usuarios;

use App\Application\Excecoes\RegraViolada;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Cadastro, edição, desativação e convites de usuários (seção 4 e T-06). */
final class GestaoUsuarios
{
    public const BROKER_CONVITES = 'convites';

    private const MSG_ULTIMO_GESTOR = 'Não é possível remover o último gestor ativo.';

    /**
     * Sem senha provisória, o usuário recebe um convite por e-mail (7 dias) para criar a senha.
     *
     * @param  array{nome: string, email: string, perfil: string, setor: ?string}  $dados
     */
    public function criar(array $dados, ?string $senhaProvisoria): Usuario
    {
        $usuario = Usuario::create([
            ...$dados,
            'senha_hash' => $senhaProvisoria ?? Str::random(48),
            'deve_trocar_senha' => $senhaProvisoria !== null,
            'ativo' => true,
        ]);

        if ($senhaProvisoria === null) {
            $this->enviarConvite($usuario);
        }

        return $usuario;
    }

    /** @param  array{nome: string, email: string, perfil: string, setor: ?string}  $dados */
    public function atualizar(Usuario $usuario, array $dados, ?string $senhaProvisoria): void
    {
        DB::transaction(function () use ($usuario, $dados, $senhaProvisoria) {
            if ($usuario->ehGestor() && $dados['perfil'] !== Usuario::PERFIL_GESTOR) {
                $this->garantirOutroGestor($usuario);
            }

            $usuario->fill($dados);
            if ($senhaProvisoria !== null) {
                $usuario->fill(['senha_hash' => $senhaProvisoria, 'deve_trocar_senha' => true]);
            }
            $usuario->save();
        });
    }

    public function alternarAtivo(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            if ($usuario->ativo && $usuario->ehGestor()) {
                $this->garantirOutroGestor($usuario);
            }

            $usuario->ativo = ! $usuario->ativo;
            $usuario->save();
        });
    }

    public function enviarConvite(Usuario $usuario): void
    {
        $token = Password::broker(self::BROKER_CONVITES)->createToken($usuario);
        $usuario->enviarConvite($token);
    }

    private function garantirOutroGestor(Usuario $usuario): void
    {
        $outros = Usuario::query()->gestoresAtivos()->whereKeyNot($usuario->id)->lockForUpdate()->pluck('id')->count();

        if ($outros === 0) {
            throw new RegraViolada(self::MSG_ULTIMO_GESTOR);
        }
    }
}
