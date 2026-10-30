<?php

namespace App\Application\Demandas;

use App\Domain\Shared\Relogio;
use App\Models\Acao;
use App\Models\Demanda;
use App\Models\Etapa;
use App\Models\Evento;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Ações do painel Detalhes: adicionar e marcar etapas, registrar ações realizadas (seção 6.5). */
final class GerenciarEtapasEAcoes
{
    public function __construct(private readonly Historico $historico, private readonly Relogio $relogio) {}

    public function adicionarEtapa(string $demandaId, string $texto, Usuario $autor): Etapa
    {
        return DB::transaction(function () use ($demandaId, $texto) {
            $demanda = $this->travar($demandaId);

            $etapa = Etapa::create([
                'demanda_id' => $demanda->id,
                'ordem' => (int) $demanda->etapas()->max('ordem') + 1,
                'texto' => trim($texto),
            ]);

            $this->novaVersao($demanda);

            return $etapa;
        });
    }

    public function marcarEtapa(string $etapaId, bool $feita, Usuario $autor): Etapa
    {
        return DB::transaction(function () use ($etapaId, $feita, $autor) {
            /** @var Etapa $etapa */
            $etapa = Etapa::query()->findOrFail($etapaId);
            $demanda = $this->travar($etapa->demanda_id);

            $etapa->update([
                'feita' => $feita,
                'feita_em' => $feita ? $this->relogio->agora() : null,
                'usuario_id' => $feita ? $autor->id : null,
            ]);

            $this->historico->registrar($demanda, Evento::ETAPA, TextosHistorico::etapa($feita, $etapa->texto), $autor);
            $this->novaVersao($demanda);

            return $etapa;
        });
    }

    public function registrarAcao(string $demandaId, CarbonImmutable $em, string $texto, Usuario $autor): Acao
    {
        return DB::transaction(function () use ($demandaId, $em, $texto, $autor) {
            $demanda = $this->travar($demandaId);

            $acao = Acao::create([
                'demanda_id' => $demanda->id,
                'em' => $em,
                'texto' => trim($texto),
                'usuario_id' => $autor->id,
            ]);

            $this->novaVersao($demanda);

            return $acao;
        });
    }

    private function travar(string $id): Demanda
    {
        return Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);
    }

    private function novaVersao(Demanda $demanda): void
    {
        $demanda->versao++;
        $demanda->save();
    }
}
