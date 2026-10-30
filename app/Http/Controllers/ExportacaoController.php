<?php

namespace App\Http\Controllers;

use App\Application\Exportacao\ExportadorCsv;
use App\Application\Relatorio\RelatorioPdf;
use App\Application\Relatorio\ServicoRelatorioDia;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Relogio;
use App\Http\Controllers\Api\RelatorioController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacaoController extends Controller
{
    /** PDF do relatório do dia (seção 8.1). */
    public function pdf(Request $request, ServicoRelatorioDia $servico, RelatorioPdf $pdf, Relogio $relogio): Response
    {
        [$relatorio, , $nome] = $servico->gerar(...RelatorioController::parametros($request, $relogio));

        return response($pdf->gerar($relatorio, $nome), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.RelatorioPdf::nomeArquivo($relatorio->dia, $nome).'"',
        ]);
    }

    /** CSV de todas as demandas (seção 8.2). */
    public function csv(ExportadorCsv $exportador, Relogio $relogio): StreamedResponse
    {
        $nome = ExportadorCsv::nomeArquivo(Fuso::dia($relogio->agora()));

        return response()->streamDownload(function () use ($exportador) {
            $saida = fopen('php://output', 'w');
            $exportador->escrever($saida);
            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
