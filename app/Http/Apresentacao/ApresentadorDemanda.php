<?php

namespace App\Http\Apresentacao;

use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Fuso;
use App\Infrastructure\Persistencia\MapeadorDemanda;
use App\Models\Demanda;
use App\Models\Etapa;
use App\Models\Evento;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Linha da demanda (seção 7.3) e painel Detalhes, com todos os textos já formatados. */
final class ApresentadorDemanda
{
    public function __construct(
        private readonly MapeadorDemanda $mapeador,
        private readonly CalculadoraStatus $status,
        private readonly AnalisePrazos $prazos,
    ) {}

    public function dados(Demanda $m): DemandaDados
    {
        return $this->mapeador->paraDados($m);
    }

    /** @return array<string, mixed> */
    public function paraArray(Demanda $m, CarbonImmutable $agora): array
    {
        $t = $this->dados($m);
        $original = $this->prazos->prazoOriginal($t);
        $prazoOriginalDifere = $original && $t->prazo && $original->getTimestamp() !== $t->prazo->getTimestamp();

        return [
            'id' => $t->id,
            'versao' => $m->versao,
            'cliente' => $t->cliente,
            'descricao' => $t->descricao,
            'canal' => $t->canal,
            'setor' => $t->setor,
            'responsavel_id' => $t->responsavelId,
            'responsavel' => $t->responsavelNome,
            'observacoes' => $t->observacoes,
            'primeiro_horario' => $t->primeiroHorario,
            'finalizada' => $t->finalizada(),
            'status' => $this->status->calcular($t, $agora)->paraArray(),
            'entrada' => $this->momento($t->entrada),
            'acordo' => $this->momento($t->acordoEm),
            'prazo' => $this->momento($t->prazo),
            'prazo_original' => $prazoOriginalDifere ? Formatador::dataHora($original) : null,
            'fim' => $t->fim ? $this->momento($t->fim) + [
                'duracao' => Formatador::duracao($t->fim->getTimestamp() - $t->entrada->getTimestamp()),
            ] : null,
            'adiamentos' => count($t->adiamentos),
            'etapas_feitas' => $t->etapasFeitas(),
            'etapas' => $m->etapas->map(fn (Etapa $e) => [
                'id' => $e->id,
                'texto' => $e->texto,
                'feita' => (bool) $e->feita,
                'feita_em' => Formatador::dataHora($e->feita_em),
            ])->values()->all(),
            'acoes' => array_map(fn ($a) => [
                'data' => Formatador::dataCurta($a->em),
                'hora' => Formatador::hora($a->em),
                'texto' => $a->texto,
                'por' => $a->usuarioNome,
            ], $t->acoes),
            'conclusao' => $t->conclusao,
            'historico' => $this->historico($m),
        ];
    }

    /** @return list<array{em: string, texto: string, por: ?string}> */
    private function historico(Demanda $m): array
    {
        if ($m->eventos->isEmpty()) {
            return [['em' => Formatador::dataHora($m->entrada), 'texto' => 'Tarefa lançada.', 'por' => null]];
        }

        return $m->eventos
            ->reject(fn (Evento $e) => $e->tipo === Evento::EXCLUIDA)
            ->map(fn (Evento $e) => [
                'em' => Formatador::dataHora($e->em),
                'texto' => $e->texto,
                'por' => $e->usuario?->nome,
            ])->values()->all();
    }

    /** @return array{data: string, hora: string, texto: string, local: string}|null */
    private function momento(?CarbonInterface $m): ?array
    {
        return $m ? [
            'data' => Formatador::dataCurta($m),
            'hora' => Formatador::hora($m),
            'texto' => Formatador::dataHora($m),
            'local' => Fuso::local($m),
        ] : null;
    }
}
