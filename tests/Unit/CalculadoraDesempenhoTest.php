<?php

namespace Tests\Unit;

use App\Domain\Equipe\CalculadoraDesempenho;
use App\Domain\Equipe\LinhaDesempenho;
use App\Domain\Equipe\Periodo;
use App\Domain\Shared\Formatador;
use PHPUnit\Framework\TestCase;
use Tests\Support\DadosExemplo;

/** Seção 14.3: equipe nos últimos 30 dias. */
class CalculadoraDesempenhoTest extends TestCase
{
    public function test_tabela_da_equipe_nos_ultimos_30_dias(): void
    {
        $linhas = (new CalculadoraDesempenho)->calcular(DadosExemplo::demandas(), [], Periodo::Dias30, DadosExemplo::agora());

        $tabela = array_map(fn (LinhaDesempenho $l) => [
            $l->nome, $l->recebidas, $l->concluidas, $l->noPrazoPct === null ? '–' : $l->noPrazoPct.'%',
            $l->adiamentos, Formatador::duracao($l->tempoMedioSegundos), $l->emAberto, $l->atrasadas,
        ], $linhas);

        $this->assertSame([
            ['Ana Souza', 3, 2, '100%', 0, '1,2 dias', 1, 0],
            ['Bruno Alves', 2, 1, '0%', 0, '1,2 dias', 1, 0],
            ['Carla Mendes', 2, 0, '–', 2, '–', 2, 0],
            ['Diego Rocha', 2, 0, '–', 1, '–', 2, 1],
            ['Eduardo Lima', 1, 0, '–', 0, '–', 1, 0],
        ], $tabela);
    }

    public function test_periodo_de_7_dias_inclui_hoje_e_os_6_anteriores(): void
    {
        $agora = DadosExemplo::agora();

        $this->assertSame('2026-10-22', Periodo::Dias7->primeiroDia($agora));
        $this->assertFalse(Periodo::Dias7->contem('2026-10-21', $agora));
        $this->assertTrue(Periodo::Tudo->contem('2020-01-01', $agora));
    }
}
