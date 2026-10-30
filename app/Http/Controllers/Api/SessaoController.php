<?php

namespace App\Http\Controllers\Api;

use App\Domain\Demandas\ListasFixas;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Relogio;
use App\Domain\Shared\Texto;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistencia\ConsultaDemandas;
use App\Infrastructure\Persistencia\FiltroSituacao;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessaoController extends Controller
{
    /** Dados de apoio da interface: usuário logado, colaboradores e listas fixas. */
    public function sessao(Request $request, Relogio $relogio): JsonResponse
    {
        /** @var Usuario $eu */
        $eu = $request->user();
        $agora = $relogio->agora();

        $usuarios = Usuario::query()->get(['id', 'nome', 'ativo'])
            ->sort(fn ($a, $b) => Texto::compararNomes($a->nome, $b->nome))
            ->map(fn (Usuario $u) => ['id' => $u->id, 'nome' => $u->nome, 'ativo' => $u->ativo])
            ->values();

        return response()->json([
            'usuario' => ['id' => $eu->id, 'nome' => $eu->nome, 'gestor' => $eu->ehGestor()],
            'usuarios' => $usuarios,
            'canais' => ListasFixas::CANAIS,
            'setores' => ListasFixas::SETORES,
            'situacoes' => array_map(fn (FiltroSituacao $f) => ['valor' => $f->value, 'rotulo' => $f->rotulo()], FiltroSituacao::cases()),
            'hoje' => Fuso::dia($agora),
            'hoje_extenso' => Formatador::extenso(Fuso::dia($agora)),
            'agora' => Fuso::local($agora),
            'agora_ms' => $agora->getTimestampMs(),
        ]);
    }

    /** Assinatura dos dados para a atualização em tempo real (consulta a cada 5 s). */
    public function versao(ConsultaDemandas $consulta, Relogio $relogio): JsonResponse
    {
        $agora = $relogio->agora();

        return response()->json([
            'assinatura' => $consulta->assinatura(),
            'hoje' => Fuso::dia($agora),
            'agora_ms' => $agora->getTimestampMs(),
        ]);
    }
}
