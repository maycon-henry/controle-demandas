<?php

namespace Database\Seeders;

use App\Application\Migracao\ImportadorPrototipo;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/** Equipe fictícia do Apêndice A (senha "demanda123") e as demandas de dados-exemplo.json. */
class DemonstracaoSeeder extends Seeder
{
    public const SENHA = 'demanda123';

    private const EQUIPE = [
        ['Henrique Duarte Pereira', 'gestor@bitaraes.local', Usuario::PERFIL_GESTOR, null],
        ['Ana Souza', 'ana@bitaraes.local', Usuario::PERFIL_COLABORADOR, 'Fiscal'],
        ['Bruno Alves', 'bruno@bitaraes.local', Usuario::PERFIL_COLABORADOR, 'Pessoal'],
        ['Carla Mendes', 'carla@bitaraes.local', Usuario::PERFIL_COLABORADOR, 'Legalização'],
        ['Diego Rocha', 'diego@bitaraes.local', Usuario::PERFIL_COLABORADOR, 'Contábil'],
        ['Eduardo Lima', 'eduardo@bitaraes.local', Usuario::PERFIL_COLABORADOR, 'Contábil'],
    ];

    public function run(ImportadorPrototipo $importador): void
    {
        foreach (self::EQUIPE as [$nome, $email, $perfil, $setor]) {
            Usuario::query()->firstOrCreate(['email' => $email], [
                'nome' => $nome, 'senha_hash' => self::SENHA, 'perfil' => $perfil, 'setor' => $setor, 'ativo' => true,
            ]);
        }

        $gestor = Usuario::query()->where('email', 'gestor@bitaraes.local')->firstOrFail();
        $documentos = json_decode(file_get_contents(database_path('data/dados-exemplo.json')), true);

        $importador->importar($documentos, $gestor);
    }
}
