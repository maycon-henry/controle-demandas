<?php

namespace Tests\Unit;

use App\Domain\Demandas\AdiamentoDados;
use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Relatorio\GeradorRelatorioDia;
use App\Domain\Relatorio\RelatorioDia;
use App\Domain\Shared\Formatador;
use PHPUnit\Framework\TestCase;
use Tests\Support\DadosExemplo;

/** Seção 14.2 e casos de borda do relatório da 14.4. */
class GeradorRelatorioDiaTest extends TestCase
{
    private function relatorio(string $dia = '2026-10-28', ?string $responsavel = null, string $agora = '2026-10-28 19:30', ?array $demandas = null): RelatorioDia
    {
        return (new GeradorRelatorioDia)->gerar($demandas ?? DadosExemplo::demandas(), $dia, $responsavel, DadosExemplo::agora($agora));
    }

    private function ids(array $lista): array
    {
        return array_map(fn (DemandaDados $t) => $t->id, $lista);
    }

    public function test_secoes_e_indicadores_de_28_10_2026(): void
    {
        $r = $this->relatorio();

        $this->assertSame(['t01', 't09', 't02'], $this->ids($r->feitas));
        $this->assertSame(['t04', 't03', 't08', 't06'], $this->ids($r->emAndamento));
        $this->assertSame(['t03', 't08'], $this->ids($r->ficouParaDepois));
        $this->assertSame(['t03', 't08'], $this->ids($r->primeiroHorario));
        $this->assertSame(['t05', 't10'], $this->ids($r->demaisAmanha));
        $this->assertSame(['t05', 't09', 't06', 't10', 't07'], $this->ids($r->novas));
        $this->assertSame(['t04'], $this->ids($r->atrasadasAnteriores));
        $this->assertSame(['t03', 't08'], $this->ids($r->adiadasNoDia));

        $this->assertCount(5, $r->novas);
        $this->assertCount(3, $r->feitas);
        $this->assertSame(5, $r->previstas);
        $this->assertSame(3, $r->cumpridas);
        $this->assertSame('60%', $r->cumpridoTexto());
    }

    public function test_novo_prazo_de_quem_ficou_para_depois(): void
    {
        $prazos = new AnalisePrazos;
        $t = DadosExemplo::demandas();

        $this->assertSame('29/10/26 08h00', Formatador::dataHora($prazos->prazoDepois($t['t03'], '2026-10-28')));
        $this->assertSame('29/10/26 08h30', Formatador::dataHora($prazos->prazoDepois($t['t08'], '2026-10-28')));
    }

    public function test_filtro_de_colaborador(): void
    {
        $r = $this->relatorio(responsavel: 'Carla Mendes');

        $this->assertSame(['t03'], $this->ids($r->ficouParaDepois));
        $this->assertSame(['t07'], $this->ids($r->novas));
        $this->assertSame([], $r->feitas);
        $this->assertTrue($r->filtrado());
    }

    public function test_atrasadas_anteriores_so_aparecem_no_dia_de_hoje(): void
    {
        $this->assertSame([], $this->relatorio(dia: '2026-10-27')->atrasadasAnteriores);
    }

    public function test_sem_nada_previsto_mostra_traco(): void
    {
        $this->assertSame('–', $this->relatorio(dia: '2026-12-25')->cumpridoTexto());
    }

    public function test_relatorio_antigo_continua_mostrando_o_que_ficou_para_depois(): void
    {
        // Caso 3: prazo 28/10 18h00, adiada em 28/10 para 29/10 08h00; relatório de 28/10 aberto em 30/10.
        $r = $this->relatorio(agora: '2026-10-30 10:00');

        $this->assertContains('t03', $this->ids($r->ficouParaDepois));
    }

    public function test_finalizada_no_dia_seguinte_conta_como_cumprida_nesse_dia(): void
    {
        // Caso 4: t03 finalizada em 29/10 10h00.
        $doc = DadosExemplo::documentos()['t03'];
        $doc['fim'] = '2026-10-29T10:00';
        $doc['conclusao'] = 'Alvará protocolado.';
        $demandas = [...DadosExemplo::demandas(), 't03' => DadosExemplo::paraDados($doc)];

        $dia28 = $this->relatorio(agora: '2026-10-29 19:00', demandas: $demandas);
        $dia29 = $this->relatorio(dia: '2026-10-29', agora: '2026-10-29 19:00', demandas: $demandas);

        $this->assertContains('t03', $this->ids($dia28->ficouParaDepois));
        $this->assertContains('t03', $this->ids($dia29->feitas));
        $this->assertGreaterThanOrEqual(1, $dia29->cumpridas);
        $this->assertSame('Concluída · com atraso',
            (new CalculadoraStatus)->calcular($demandas['t03'], DadosExemplo::agora('2026-10-29 19:00'))->exibicao());
    }

    public function test_dois_adiamentos_no_mesmo_dia_usam_o_ultimo(): void
    {
        // Caso 5: 18h → 20h → amanhã 08h.
        $a = fn ($m) => DadosExemplo::agora($m);
        $t = new DemandaDados(
            id: 'x', entrada: $a('2026-10-28 08:00'), prazo: $a('2026-10-29 08:00'), primeiroHorario: true,
            adiamentos: [
                new AdiamentoDados($a('2026-10-28 18:00'), $a('2026-10-28 20:00'), $a('2026-10-28 17:00')),
                new AdiamentoDados($a('2026-10-28 20:00'), $a('2026-10-29 08:00'), $a('2026-10-28 19:00')),
            ],
        );

        $this->assertSame('29/10/26 08h00', Formatador::dataHora((new AnalisePrazos)->prazoDepois($t, '2026-10-28')));
        $this->assertCount(2, $t->adiamentos);
    }

    public function test_sem_prazo_nunca_aparece_em_ficou_nem_em_amanha(): void
    {
        $t = new DemandaDados(id: 'x', entrada: DadosExemplo::agora('2026-10-28 08:00'));
        $r = $this->relatorio(demandas: [$t]);

        $this->assertSame([], $r->ficouParaDepois);
        $this->assertSame([], $r->previstasAmanha());
    }

    public function test_acao_hoje_em_demanda_finalizada_ontem_aparece_em_andamento(): void
    {
        // Caso 10.
        $doc = DadosExemplo::documentos()['t02'];
        $doc['acoes'][] = ['em' => '2026-10-29T09:00', 'txt' => 'Enviado comprovante ao cliente.'];
        $r = $this->relatorio(dia: '2026-10-29', agora: '2026-10-29 12:00', demandas: [DadosExemplo::paraDados($doc)]);

        $this->assertSame(['t02'], $this->ids($r->emAndamento));
    }

    public function test_virada_de_dia_a_meia_noite(): void
    {
        // Caso 11: lançada às 23h50 e finalizada às 00h10 do dia seguinte.
        $t = new DemandaDados(id: 'x', entrada: DadosExemplo::agora('2026-10-28 23:50'), fim: DadosExemplo::agora('2026-10-29 00:10'), conclusao: 'ok');

        $this->assertSame(['x'], $this->ids($this->relatorio(demandas: [$t], agora: '2026-10-29 01:00')->novas));
        $this->assertSame(['x'], $this->ids($this->relatorio(dia: '2026-10-29', demandas: [$t], agora: '2026-10-29 01:00')->feitas));
        $this->assertSame('1h', Formatador::duracao($t->fim->getTimestamp() - $t->entrada->getTimestamp()));
    }
}
