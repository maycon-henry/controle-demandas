<?php

namespace App\Application\Demandas;

use App\Domain\Shared\Relogio;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Exclusão lógica: a demanda some das telas mas permanece no banco para auditoria. */
final class ExcluirDemanda
{
    public function __construct(private readonly Historico $historico, private readonly Relogio $relogio) {}

    public function executar(string $id, Usuario $autor): void
    {
        DB::transaction(function () use ($id, $autor) {
            /** @var Demanda $demanda */
            $demanda = Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);

            $demanda->excluido_em = $this->relogio->agora();
            $demanda->versao++;
            $demanda->save();

            $this->historico->registrar($demanda, Evento::EXCLUIDA, TextosHistorico::excluida(), $autor);
        });
    }
}
