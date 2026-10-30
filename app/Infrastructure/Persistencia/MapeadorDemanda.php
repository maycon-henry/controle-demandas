<?php

namespace App\Infrastructure\Persistencia;

use App\Domain\Demandas\AcaoDados;
use App\Domain\Demandas\AdiamentoDados;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\EtapaDados;
use App\Domain\Shared\Fuso;
use App\Models\Acao;
use App\Models\Adiamento;
use App\Models\Demanda;
use App\Models\Etapa;

/** Converte o modelo Eloquent no retrato de domínio usado pelas regras. */
final class MapeadorDemanda
{
    public function paraDados(Demanda $m): DemandaDados
    {
        $carregado = fn (string $relacao) => $m->relationLoaded($relacao) ? $m->getRelation($relacao) : collect();

        return new DemandaDados(
            id: $m->id,
            entrada: Fuso::parse($m->entrada),
            cliente: $m->relationLoaded('cliente') ? (string) $m->cliente?->nome : '',
            descricao: $m->descricao,
            canal: $m->canal,
            setor: $m->setor,
            responsavelId: $m->responsavel_id,
            responsavelNome: $m->relationLoaded('responsavel') ? $m->responsavel?->nome : null,
            acordoEm: Fuso::parseOuNulo($m->acordo_em),
            prazo: Fuso::parseOuNulo($m->prazo),
            primeiroHorario: (bool) $m->primeiro_horario,
            fim: Fuso::parseOuNulo($m->fim),
            conclusao: $m->conclusao,
            observacoes: $m->observacoes,
            adiamentos: $carregado('adiamentos')->map(fn (Adiamento $a) => new AdiamentoDados(
                Fuso::parse($a->de), Fuso::parse($a->para), Fuso::parse($a->em), $a->motivo,
            ))->all(),
            etapas: $carregado('etapas')->map(fn (Etapa $e) => new EtapaDados(
                $e->texto, (bool) $e->feita, Fuso::parseOuNulo($e->feita_em), $e->id,
            ))->all(),
            acoes: $carregado('acoes')->map(fn (Acao $a) => new AcaoDados(
                Fuso::parse($a->em), $a->texto, $a->relationLoaded('usuario') ? $a->usuario?->nome : null,
            ))->all(),
        );
    }
}
