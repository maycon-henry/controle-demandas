<?php

namespace App\Http\Apresentacao;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Relatorio\RelatorioDia;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Fuso;
use App\Models\Demanda;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Tela do Relatório do dia (seções 6.2, 7.2 e textos da seção 9). */
final class ApresentadorRelatorio
{
    public function __construct(private readonly ApresentadorDemanda $demandas) {}

    /** "Hoje, quarta-feira, 28 de outubro de 2026 · Carla Mendes" */
    public static function titulo(RelatorioDia $r, CarbonImmutable $agora, ?string $nomeFiltro): string
    {
        $hoje = Fuso::dia($agora);
        $prefixo = match ($r->dia) {
            $hoje => 'Hoje, ',
            Fuso::somarDias($hoje, -1) => 'Ontem, ',
            Fuso::somarDias($hoje, 1) => 'Amanhã, ',
            default => '',
        };

        $titulo = $prefixo.Formatador::extenso($r->dia);

        return $nomeFiltro ? $titulo.' · '.$nomeFiltro : $titulo;
    }

    /**
     * @param  Collection<string, Demanda>  $modelos  modelos indexados por id
     * @return array<string, mixed>
     */
    public function paraArray(RelatorioDia $r, Collection $modelos, CarbonImmutable $agora, ?string $nomeFiltro): array
    {
        $diaSemanaAmanha = Formatador::diaDaSemana($r->amanha);
        $amanhaCurta = Formatador::dataCurta(Fuso::inicioDoDia($r->amanha));

        $secoes = [
            $this->secao('feitas', 'O que foi feito', 'verde', null, 'Nenhuma tarefa finalizada neste dia.', $r->feitas),
            $this->secao('em_andamento', 'Em andamento: ações realizadas neste dia', null,
                'Tarefas que tiveram ação registrada ou etapa concluída no dia, mas ainda não foram finalizadas nele. Abra “Detalhes” para ver o que foi feito.',
                'Nenhuma ação registrada em tarefas não finalizadas.', $r->emAndamento),
            $this->secao('ficou', 'Era para este dia e ficou para depois', 'vermelho',
                'Tarefas com prazo neste dia que não foram finalizadas até o fim dele. Use “Adiar” para registrar o novo prazo e o motivo.',
                'Tudo o que estava previsto foi entregue.', $r->ficouParaDepois),
            $this->secao('primeiro_horario', 'Primeiro horário de '.$diaSemanaAmanha, 'azul',
                "Tarefas marcadas para o início do expediente do dia seguinte ($amanhaCurta).",
                'Nada marcado para o primeiro horário.', $r->primeiroHorario),
            $this->secao('demais_amanha', 'Demais tarefas previstas para '.$diaSemanaAmanha, null, null,
                'Nenhuma outra tarefa com prazo no dia seguinte.', $r->demaisAmanha),
            $this->secao('novas', 'Demandas novas que entraram', null, null,
                'Nenhuma demanda nova registrada neste dia.', $r->novas),
        ];

        if ($r->atrasadasAnteriores !== []) {
            $secoes[] = $this->secao('atrasadas', 'Atrasadas de dias anteriores, ainda em aberto', 'vermelho', null, '', $r->atrasadasAnteriores);
        }

        return [
            'data' => $r->dia,
            'hoje' => Fuso::dia($agora),
            'titulo' => self::titulo($r, $agora, $nomeFiltro),
            'indicadores' => [
                'entraram' => count($r->novas),
                'concluidas' => count($r->feitas),
                'ficaram' => count($r->ficouParaDepois),
                'primeiro_horario' => count($r->primeiroHorario),
                'cumprido' => $r->cumpridoTexto(),
            ],
            'secoes' => array_map(fn (array $s) => [
                ...$s,
                'itens' => array_map(fn (DemandaDados $t) => $this->demandas->paraArray($modelos[$t->id], $agora), $s['itens']),
            ], $secoes),
        ];
    }

    /** @param  list<DemandaDados>  $itens */
    private function secao(string $chave, string $titulo, ?string $cor, ?string $subtitulo, string $vazio, array $itens): array
    {
        return compact('chave', 'titulo', 'cor', 'subtitulo', 'vazio', 'itens');
    }
}
