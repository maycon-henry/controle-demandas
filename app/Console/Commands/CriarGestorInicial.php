<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;

/**
 * Cria o primeiro gestor na instalação (seção 4.1) a partir de GESTOR_NOME, GESTOR_EMAIL e
 * GESTOR_SENHA. Não faz nada se já existir algum gestor, então pode rodar a cada deploy.
 */
class CriarGestorInicial extends Command
{
    protected $signature = 'usuarios:gestor-inicial';

    protected $description = 'Cria o primeiro gestor a partir das variáveis de ambiente, se ainda não houver nenhum';

    public function handle(): int
    {
        if (Usuario::query()->where('perfil', Usuario::PERFIL_GESTOR)->exists()) {
            $this->line('Já existe gestor cadastrado; nada a fazer.');

            return self::SUCCESS;
        }

        ['nome' => $nome, 'email' => $email, 'senha' => $senha] = config('escritorio.gestor_inicial');

        if (! $nome || ! $email || ! $senha) {
            $this->warn('Defina GESTOR_NOME, GESTOR_EMAIL e GESTOR_SENHA para criar o primeiro gestor.');

            return self::SUCCESS;
        }

        Usuario::create([
            'nome' => $nome,
            'email' => mb_strtolower($email),
            'senha_hash' => $senha,
            'perfil' => Usuario::PERFIL_GESTOR,
            'ativo' => true,
            // A senha veio de uma variável de ambiente: obriga a troca no primeiro login.
            'deve_trocar_senha' => true,
        ]);

        $this->info("Gestor $nome criado. Troque a senha no primeiro acesso.");

        return self::SUCCESS;
    }
}
