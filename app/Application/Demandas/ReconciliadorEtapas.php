<?php

namespace App\Application\Demandas;

use App\Models\Demanda;
use App\Models\Etapa;
use App\Models\Usuario;

/**
 * Reconcilia as etapas digitadas no formulário (uma por linha) com as já gravadas (seção 6.5):
 * linhas iguais mantêm o estado, linhas novas entram (feitas, se a demanda já está finalizada)
 * e linhas removidas apagam a etapa.
 */
final class ReconciliadorEtapas
{
    /** @param  list<string>  $linhas */
    public function reconciliar(Demanda $demanda, array $linhas, Usuario $autor): void
    {
        $existentes = $demanda->etapas()->get()->groupBy('texto');
        $mantidas = [];

        foreach ($linhas as $posicao => $texto) {
            /** @var Etapa|null $etapa */
            $etapa = $existentes->get($texto)?->shift();

            if ($etapa) {
                $etapa->update(['ordem' => $posicao + 1]);
            } else {
                $etapa = Etapa::create([
                    'demanda_id' => $demanda->id,
                    'ordem' => $posicao + 1,
                    'texto' => $texto,
                    'feita' => $demanda->fim !== null,
                    'feita_em' => $demanda->fim,
                    'usuario_id' => $demanda->fim !== null ? $autor->id : null,
                ]);
            }

            $mantidas[] = $etapa->id;
        }

        $demanda->etapas()->whereNotIn('id', $mantidas)->delete();
    }
}
