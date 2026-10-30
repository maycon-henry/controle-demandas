<?php

namespace App\Http\Controllers\Api;

use App\Application\Relatorio\ServicoRelatorioDia;
use App\Application\Relatorio\TextoWhatsapp;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Relogio;
use App\Http\Apresentacao\ApresentadorRelatorio;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function __construct(private readonly ServicoRelatorioDia $servico, private readonly Relogio $relogio) {}

    /** Seções e indicadores já calculados (seção 6.2). */
    public function dia(Request $request, ApresentadorRelatorio $apresentador): JsonResponse
    {
        [$relatorio, $modelos, $nome] = $this->servico->gerar(...self::parametros($request, $this->relogio));

        return response()->json($apresentador->paraArray($relatorio, $modelos, $this->relogio->agora(), $nome));
    }

    /** Texto para WhatsApp (seção 8.3). */
    public function texto(Request $request, TextoWhatsapp $texto): JsonResponse
    {
        [$relatorio, , $nome] = $this->servico->gerar(...self::parametros($request, $this->relogio));

        return response()->json(['texto' => $texto->gerar($relatorio, $nome)]);
    }

    /** @return array{0: string, 1: ?string} dia AAAA-MM-DD e responsável (ou null) */
    public static function parametros(Request $request, Relogio $relogio): array
    {
        $data = (string) $request->query('data', '');
        $dia = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4))
            ? $data
            : Fuso::dia($relogio->agora());

        $responsavel = (string) $request->query('responsavel', '');

        return [$dia, preg_match('/^[0-9a-f-]{36}$/i', $responsavel) ? $responsavel : null];
    }
}
