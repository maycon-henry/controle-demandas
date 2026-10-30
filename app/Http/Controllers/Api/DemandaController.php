<?php

namespace App\Http\Controllers\Api;

use App\Application\Demandas\AtualizarDemanda;
use App\Application\Demandas\CriarDemanda;
use App\Application\Demandas\ExcluirDemanda;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Relogio;
use App\Http\Apresentacao\ApresentadorDemanda;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarDemandaRequest;
use App\Infrastructure\Persistencia\ConsultaDemandas;
use App\Infrastructure\Persistencia\FiltroSituacao;
use App\Infrastructure\Persistencia\FiltrosLista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DemandaController extends Controller
{
    private const POR_PAGINA = 50;

    public function __construct(
        private readonly ConsultaDemandas $consulta,
        private readonly ApresentadorDemanda $apresentador,
        private readonly Relogio $relogio,
    ) {}

    /** Aba "Todas as tarefas": indicadores, filtros e paginação de 50 (seção 7.5). */
    public function index(Request $request): JsonResponse
    {
        $agora = $this->relogio->agora();
        $paginas = max(1, (int) $request->query('paginas', 1));
        $filtros = new FiltrosLista(
            busca: trim((string) $request->query('busca', '')),
            situacao: FiltroSituacao::tryFrom((string) $request->query('situacao', '')) ?? FiltroSituacao::Todas,
            canal: (string) $request->query('canal', ''),
            setor: (string) $request->query('setor', ''),
            responsavel: (string) $request->query('responsavel', ''),
        );

        [$itens, $total] = $this->consulta->listar($filtros, $agora, $paginas * self::POR_PAGINA);
        $ind = $this->consulta->indicadoresLista($agora);

        return response()->json([
            'indicadores' => [
                'pendentes' => $ind['pendentes'],
                'adiadas' => $ind['adiadas'],
                'atrasadas' => $ind['atrasadas'],
                'no_prazo' => $ind['concluidas'] > 0 ? round(100 * $ind['no_prazo'] / $ind['concluidas']).'%' : '–',
                'tempo_medio' => Formatador::duracao($ind['duracao_media']),
            ],
            'total_geral' => $ind['total'],
            'total' => $total,
            'itens' => $itens->map(fn ($m) => $this->apresentador->paraArray($m, $agora))->all(),
            'tem_mais' => $total > $itens->count(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->apresentador->paraArray($this->consulta->encontrar($id), $this->relogio->agora()));
    }

    public function store(SalvarDemandaRequest $request, CriarDemanda $criar): JsonResponse
    {
        $demanda = $criar->executar($request->dados(), $request->user());

        return response()->json(['message' => 'Tarefa registrada', 'id' => $demanda->id], 201);
    }

    public function update(string $id, SalvarDemandaRequest $request, AtualizarDemanda $atualizar): JsonResponse
    {
        $atualizar->executar($id, (int) $request->validated('versao'), $request->dados(), $request->user());

        return response()->json(['message' => 'Tarefa atualizada']);
    }

    public function destroy(string $id, Request $request, ExcluirDemanda $excluir): JsonResponse
    {
        Gate::authorize('excluir-demanda');
        $excluir->executar($id, $request->user());

        return response()->json(['message' => 'Tarefa excluída']);
    }
}
