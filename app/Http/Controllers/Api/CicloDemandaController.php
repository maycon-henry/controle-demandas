<?php

namespace App\Http\Controllers\Api;

use App\Application\Demandas\AdiarDemanda;
use App\Application\Demandas\FinalizarDemanda;
use App\Application\Demandas\GerenciarEtapasEAcoes;
use App\Application\Demandas\ReabrirDemanda;
use App\Domain\Shared\Fuso;
use App\Http\Controllers\Controller;
use App\Http\Requests\CicloDemandaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Adiar, finalizar, reabrir, etapas e ações (seções 6.3 a 6.5). */
class CicloDemandaController extends Controller
{
    public function adiar(string $id, CicloDemandaRequest $request, AdiarDemanda $adiar): JsonResponse
    {
        $adiar->executar(
            $id,
            Fuso::parse($request->validated('para')),
            $request->validated('motivo'),
            $request->boolean('primeiro_horario'),
            $request->user(),
        );

        return $this->ok('Adiamento registrado');
    }

    public function finalizar(string $id, CicloDemandaRequest $request, FinalizarDemanda $finalizar): JsonResponse
    {
        $finalizar->executar(
            $id,
            Fuso::parse($request->validated('fim')),
            $request->validated('conclusao'),
            $request->boolean('marcar_etapas'),
            $request->user(),
        );

        return $this->ok('Tarefa concluída');
    }

    public function reabrir(string $id, Request $request, ReabrirDemanda $reabrir): JsonResponse
    {
        $reabrir->executar($id, $request->user());

        return $this->ok('Tarefa reaberta');
    }

    public function adicionarEtapa(string $id, CicloDemandaRequest $request, GerenciarEtapasEAcoes $etapas): JsonResponse
    {
        $etapas->adicionarEtapa($id, $request->validated('texto'), $request->user());

        return $this->ok('Etapa adicionada');
    }

    public function marcarEtapa(string $id, CicloDemandaRequest $request, GerenciarEtapasEAcoes $etapas): JsonResponse
    {
        $etapas->marcarEtapa($id, $request->boolean('feita'), $request->user());

        return $this->ok($request->boolean('feita') ? 'Etapa concluída' : 'Etapa desmarcada');
    }

    public function registrarAcao(string $id, CicloDemandaRequest $request, GerenciarEtapasEAcoes $acoes): JsonResponse
    {
        $acoes->registrarAcao($id, Fuso::parse($request->validated('em')), $request->validated('texto'), $request->user());

        return $this->ok('Ação registrada');
    }

    private function ok(string $mensagem): JsonResponse
    {
        return response()->json(['message' => $mensagem]);
    }
}
