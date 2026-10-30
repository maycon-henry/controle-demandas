<?php

namespace App\Http\Controllers\Api;

use App\Domain\Equipe\CalculadoraDesempenho;
use App\Domain\Equipe\LinhaDesempenho;
use App\Domain\Equipe\Periodo;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Relogio;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistencia\ConsultaDemandas;
use App\Infrastructure\Persistencia\MapeadorDemanda;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Aba Equipe: desempenho por colaborador (seção 6.7). */
class EquipeController extends Controller
{
    public function index(
        Request $request,
        ConsultaDemandas $consulta,
        MapeadorDemanda $mapeador,
        CalculadoraDesempenho $calculadora,
        Relogio $relogio,
    ): JsonResponse {
        $periodo = Periodo::deTexto($request->query('periodo'));
        $demandas = $consulta->paraDesempenho()->map(fn ($m) => $mapeador->paraDados($m));

        // Sempre aparecem: colaboradores ativos e qualquer pessoa que já tenha sido responsável.
        $pessoas = Usuario::query()
            ->where(fn ($q) => $q->where(fn ($w) => $w->where('ativo', true)->where('perfil', Usuario::PERFIL_COLABORADOR))
                ->orWhereIn('id', $demandas->pluck('responsavelId')->filter()->unique()))
            ->get()
            ->mapWithKeys(fn (Usuario $u) => [$u->id => ['nome' => $u->nome, 'ativo' => $u->ativo]])
            ->all();

        $linhas = $calculadora->calcular($demandas, $pessoas, $periodo, $relogio->agora());
        $temDados = $demandas->contains(fn ($t) => $t->responsavelId !== null);

        return response()->json([
            'periodo' => $periodo->value,
            'periodos' => array_map(fn (Periodo $p) => ['valor' => $p->value, 'rotulo' => $p->rotulo()], Periodo::cases()),
            'tem_dados' => $temDados,
            'linhas' => array_map(fn (LinhaDesempenho $l) => [
                'id' => $l->responsavelId,
                'nome' => $l->nome,
                'ativo' => $l->ativo,
                'recebidas' => $l->recebidas,
                'concluidas' => $l->concluidas,
                'no_prazo' => $l->noPrazoPct === null ? '–' : $l->noPrazoPct.'%',
                'no_prazo_cor' => match (true) {
                    $l->noPrazoPct === null => null,
                    $l->noPrazoPct >= 90 => 'verde',
                    $l->noPrazoPct < 70 => 'vermelho',
                    default => null,
                },
                'adiamentos' => $l->adiamentos,
                'tempo_medio' => Formatador::duracao($l->tempoMedioSegundos),
                'em_aberto' => $l->emAberto,
                'atrasadas' => $l->atrasadas,
            ], $linhas),
        ]);
    }
}
