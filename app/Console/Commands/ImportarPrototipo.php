<?php

namespace App\Console\Commands;

use App\Application\Migracao\ImportadorPrototipo;
use App\Application\Relatorio\ServicoRelatorioDia;
use App\Models\Demanda;
use App\Models\Usuario;
use Illuminate\Console\Command;

/** T-22: importa a exportação JSON do protótipo e imprime um relatório de conferência. */
class ImportarPrototipo extends Command
{
    protected $signature = 'demandas:importar
        {arquivo : Caminho do JSON exportado do protótipo}
        {--conferir=* : Datas (AAAA-MM-DD) para imprimir os números do Relatório do dia}';

    protected $description = 'Importa as demandas do protótipo (idempotente: rodar duas vezes não duplica)';

    public function handle(ImportadorPrototipo $importador, ServicoRelatorioDia $relatorios): int
    {
        $caminho = $this->argument('arquivo');
        if (! is_readable($caminho)) {
            $this->error("Arquivo não encontrado: $caminho");

            return self::FAILURE;
        }

        $documentos = json_decode(file_get_contents($caminho), true, flags: JSON_THROW_ON_ERROR);
        $gestor = Usuario::query()->gestoresAtivos()->orderBy('criado_em')->first();
        if (! $gestor) {
            $this->error('Cadastre um gestor antes de importar (php artisan usuarios:gestor-inicial).');

            return self::FAILURE;
        }

        $resultado = $importador->importar($documentos, $gestor);

        $this->info(sprintf('Documentos no arquivo: %d · importados: %d · já existentes (ignorados): %d',
            count($documentos), $resultado['importadas'], $resultado['ignoradas']));
        $this->line('Demandas no banco (não excluídas): '.Demanda::query()->ativas()->count());

        if ($resultado['usuarios_criados'] !== []) {
            $this->warn('Responsáveis criados como inativos (ajuste o e-mail e reative em Usuários): '.implode(', ', $resultado['usuarios_criados']));
        }

        foreach ($this->option('conferir') as $dia) {
            [$r] = $relatorios->gerar($dia, null);
            $this->table(['Dia', 'Entraram', 'Concluídas', 'Ficaram p/ depois', '1º horário amanhã', 'Cumprido'], [[
                $dia, count($r->novas), count($r->feitas), count($r->ficouParaDepois), count($r->primeiroHorario), $r->cumpridoTexto(),
            ]]);
        }

        return self::SUCCESS;
    }
}
