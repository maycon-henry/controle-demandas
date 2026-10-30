<?php

namespace App\Application\Relatorio;

use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Relatorio\RelatorioDia;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Relogio;
use App\Domain\Shared\Texto;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF do relatório do dia (seção 8.1). */
final class RelatorioPdf
{
    public function __construct(
        private readonly CalculadoraStatus $status,
        private readonly AnalisePrazos $prazos,
        private readonly Relogio $relogio,
    ) {}

    public static function nomeArquivo(string $dia, ?string $nomeFiltro): string
    {
        return 'relatorio-demandas-'.$dia.($nomeFiltro ? '-'.Texto::slug($nomeFiltro) : '').'.pdf';
    }

    public function gerar(RelatorioDia $r, ?string $nomeFiltro): string
    {
        $agora = $this->relogio->agora();

        $secoes = [
            ['Tarefas finalizadas', 'verde', $r->feitas, 'Nenhuma tarefa finalizada neste dia.'],
            ['Em andamento: ações realizadas neste dia', 'azul', $r->emAndamento, 'Nenhuma ação registrada em tarefas não finalizadas.'],
            ['Pendentes: eram para este dia e não foram finalizadas', 'vermelho', $r->ficouParaDepois, 'Tudo o que estava previsto foi entregue.'],
            ['Adiadas neste dia', 'ambar', $r->adiadasNoDia, 'Nenhum prazo foi adiado neste dia.'],
            ['Primeiro horário de amanhã', 'azul', $r->primeiroHorario, 'Nada marcado para o primeiro horário.'],
            ['Demais tarefas previstas para amanhã', null, $r->demaisAmanha, 'Nenhuma outra tarefa com prazo no dia seguinte.'],
            ['Demandas novas que entraram', null, $r->novas, 'Nenhuma demanda nova registrada neste dia.'],
        ];

        if ($r->atrasadasAnteriores !== []) {
            $secoes[] = ['Atrasadas de dias anteriores', 'vermelho', $r->atrasadasAnteriores, ''];
        }

        $html = view('pdf.relatorio', [
            'subtitulo' => Formatador::primeiraMaiuscula(Formatador::extenso($r->dia)).' · '.($nomeFiltro ?? 'Toda a equipe'),
            'cartoes' => [
                'Entraram' => count($r->novas),
                'Concluídas' => count($r->feitas),
                'Ficaram p/ depois' => count($r->ficouParaDepois),
                'Adiadas no dia' => count($r->adiadasNoDia),
                '1º horário amanhã' => count($r->primeiroHorario),
                'Cumprido do previsto' => $r->cumpridoTexto(),
            ],
            'secoes' => array_map(fn (array $s) => [
                'titulo' => $s[0],
                'cor' => $s[1],
                'vazio' => $s[3],
                'linhas' => array_map(fn (DemandaDados $t) => $this->linha($t, $agora), $s[2]),
            ], $secoes),
            'resumo' => $r->filtrado() ? null : $this->resumoPorColaborador($r),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fonte = $dompdf->getFontMetrics()->getFont('Helvetica');
        $mm = 72 / 25.4;
        $y = $canvas->get_height() - 8 * $mm;
        $canvas->page_text(14 * $mm, $y, 'Gerado em '.Formatador::dataHora($agora), $fonte, 7, [0.45, 0.5, 0.58]);
        $canvas->page_text($canvas->get_width() - 40 * $mm, $y, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fonte, 7, [0.45, 0.5, 0.58]);

        return $dompdf->output();
    }

    /** @return array<string, list<string>|string> */
    private function linha(DemandaDados $t, $agora): array
    {
        $demanda = ['Solicitado: '.$t->descricao];
        if ($t->etapas !== []) {
            $demanda[] = sprintf('Etapas (%d/%d):', $t->etapasFeitas(), count($t->etapas));
            foreach ($t->etapas as $e) {
                $demanda[] = '   '.($e->feita ? '[x] '.$e->texto.' – '.Formatador::dataHora($e->feitaEm) : '[ ] '.$e->texto);
            }
        }
        if ($t->acoes !== []) {
            $demanda[] = 'Ações realizadas:';
            foreach ($t->acoes as $a) {
                $demanda[] = '   '.Formatador::dataHora($a->em).' – '.$a->texto;
            }
        }

        $original = $this->prazos->prazoOriginal($t);
        $acordos = [
            $t->acordoEm ? 'Firmado em '.Formatador::dataHora($t->acordoEm) : 'Data do acordo não informada',
            $original ? 'Prazo acordado: '.Formatador::dataHora($original) : 'Sem prazo acordado',
        ];
        foreach ($t->adiamentos as $i => $a) {
            $acordos[] = sprintf('%dº adiamento em %s: novo prazo %s%s', $i + 1, Formatador::dataHora($a->em),
                Formatador::dataHora($a->para), $a->motivo ? ' ('.$a->motivo.')' : '');
        }
        if ($t->foiAdiada() && $t->prazo) {
            $acordos[] = 'Prazo atual: '.Formatador::dataHora($t->prazo);
        }

        $status = $this->status->calcular($t, $agora);
        $finalizacao = $t->finalizada()
            ? array_values(array_filter([$status->completo(), Formatador::dataHora($t->fim),
                $t->conclusao ? 'Como foi finalizada: '.$t->conclusao : null]))
            : [$status->titulo, $status->complemento];

        return [
            'cliente' => $t->cliente,
            'demanda' => $demanda,
            'responsavel' => [$t->nomeResponsavel(), $t->setor],
            'entrada' => [Formatador::dataHora($t->entrada), 'via '.$t->canal],
            'acordos' => $acordos,
            'finalizacao' => $finalizacao,
        ];
    }

    /** @return list<array{nome: string, entraram: int, finalizadas: int, pendentes: int, adiadas: int, amanha: int}> */
    private function resumoPorColaborador(RelatorioDia $r): array
    {
        $contagens = [
            'entraram' => $r->novas, 'finalizadas' => $r->feitas, 'pendentes' => $r->ficouParaDepois,
            'adiadas' => $r->adiadasNoDia, 'amanha' => $r->previstasAmanha(),
        ];

        $linhas = [];
        foreach ($contagens as $coluna => $lista) {
            foreach ($lista as $t) {
                $nome = $t->nomeResponsavel();
                $linhas[$nome] ??= ['nome' => $nome, 'entraram' => 0, 'finalizadas' => 0, 'pendentes' => 0, 'adiadas' => 0, 'amanha' => 0];
                $linhas[$nome][$coluna]++;
            }
        }

        uksort($linhas, [Texto::class, 'compararNomes']);

        return array_values($linhas);
    }
}
