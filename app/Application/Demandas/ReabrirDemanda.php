<?php

namespace App\Application\Demandas;

use App\Application\Excecoes\RegraViolada;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Limpa o fim e mantém a conclusão anterior (seção 6.4). */
final class ReabrirDemanda
{
    public function __construct(private readonly Historico $historico) {}

    public function executar(string $id, Usuario $autor): Demanda
    {
        return DB::transaction(function () use ($id, $autor) {
            /** @var Demanda $demanda */
            $demanda = Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);

            if ($demanda->fim === null) {
                throw new RegraViolada('Esta tarefa não está finalizada.');
            }

            $demanda->fill(['fim' => null, 'versao' => $demanda->versao + 1])->save();
            $this->historico->registrar($demanda, Evento::REABERTA, TextosHistorico::reaberta(), $autor);

            return $demanda;
        });
    }
}
