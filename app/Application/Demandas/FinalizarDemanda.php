<?php

namespace App\Application\Demandas;

use App\Application\Excecoes\RegraViolada;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class FinalizarDemanda
{
    public const MSG_FIM_ANTES_DA_ENTRADA = 'A data de finalização está antes da entrada. Confira os horários.';

    public function __construct(private readonly Historico $historico) {}

    public function executar(string $id, CarbonImmutable $fim, string $conclusao, bool $marcarEtapas, Usuario $autor): Demanda
    {
        return DB::transaction(function () use ($id, $fim, $conclusao, $marcarEtapas, $autor) {
            /** @var Demanda $demanda */
            $demanda = Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);

            if ($demanda->fim !== null) {
                throw new RegraViolada('Esta tarefa já foi finalizada.');
            }
            if ($fim < $demanda->entrada) {
                throw new RegraViolada(self::MSG_FIM_ANTES_DA_ENTRADA);
            }

            if ($marcarEtapas) {
                $demanda->etapas()->where('feita', false)->update([
                    'feita' => true,
                    'feita_em' => $fim->format('Y-m-d H:i:sP'),
                    'usuario_id' => $autor->id,
                ]);
            }

            $demanda->fill([
                'fim' => $fim,
                'conclusao' => trim($conclusao),
                'versao' => $demanda->versao + 1,
            ])->save();

            $this->historico->registrar($demanda, Evento::FINALIZADA, TextosHistorico::finalizada($conclusao), $autor, $fim);

            return $demanda;
        });
    }
}
