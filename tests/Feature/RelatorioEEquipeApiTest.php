<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T-15, T-16, T-17, T-18: números da seção 14 pela API e saídas PDF/CSV. */
class RelatorioEEquipeApiTest extends TestCase
{
    use ComDadosDeExemplo, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->carregarExemplo();
        $this->actingAs($this->colaborador());
    }

    public function test_relatorio_do_dia_pela_api(): void
    {
        $r = $this->getJson('/api/relatorio-dia?data=2026-10-28')->assertOk()->json();

        $this->assertSame('Hoje, quarta-feira, 28 de outubro de 2026', $r['titulo']);
        $this->assertSame(['entraram' => 5, 'concluidas' => 3, 'ficaram' => 2, 'primeiro_horario' => 2, 'cumprido' => '60%'], $r['indicadores']);

        $secoes = collect($r['secoes'])->mapWithKeys(fn ($s) => [$s['titulo'] => collect($s['itens'])->pluck('cliente')->all()]);
        $this->assertSame(['Clínica Vida Plena', 'Transportes Rota Sul'], $secoes['Primeiro horário de quinta-feira']);
        $this->assertSame(['Restaurante Sabor da Casa', 'Mercado Bom Preço'], $secoes['Demais tarefas previstas para quinta-feira']);
        $this->assertSame(['Mercado Bom Preço'], $secoes['Atrasadas de dias anteriores, ainda em aberto']);

        $clinica = collect($r['secoes'][2]['itens'])->firstWhere('cliente', 'Clínica Vida Plena');
        $this->assertSame('27/10/26 18h00', $clinica['prazo_original']);
        $this->assertSame(['titulo' => 'Adiada para 29/10/26', 'complemento' => '08h00, 1º horário'],
            array_intersect_key($clinica['status'], array_flip(['titulo', 'complemento'])));
    }

    public function test_relatorio_filtrado_por_colaborador(): void
    {
        $carla = Usuario::query()->where('nome', 'Carla Mendes')->first();
        $r = $this->getJson("/api/relatorio-dia?data=2026-10-28&responsavel={$carla->id}")->json();

        $this->assertSame('Hoje, quarta-feira, 28 de outubro de 2026 · Carla Mendes', $r['titulo']);
        $this->assertSame(1, $r['indicadores']['ficaram']);
        $this->assertSame(1, $r['indicadores']['entraram']);
        $this->assertSame(0, $r['indicadores']['concluidas']);
    }

    public function test_todas_as_tarefas_indicadores_filtros_e_busca(): void
    {
        $lista = $this->getJson('/api/demandas')->json();
        $this->assertSame(['pendentes' => 5, 'adiadas' => 2, 'atrasadas' => 1, 'no_prazo' => '67%', 'tempo_medio' => '1,2 dias'], $lista['indicadores']);
        // Não finalizadas primeiro, entrada mais recente primeiro.
        $this->assertSame('Studio Arte & Forma', $lista['itens'][0]['cliente']);
        $this->assertTrue(end($lista['itens'])['finalizada']);

        $adiadas = $this->getJson('/api/demandas?situacao=adiada')->json('itens');
        $this->assertSame(['Transportes Rota Sul', 'Clínica Vida Plena'], array_column($adiadas, 'cliente'));

        $this->assertSame(['Clínica Vida Plena'], array_column($this->getJson('/api/demandas?busca=CLINICA')->json('itens'), 'cliente'));
        $this->assertSame(3, $this->getJson('/api/demandas?busca=souza')->json('total'));
    }

    public function test_equipe_ultimos_30_dias(): void
    {
        $linhas = collect($this->getJson('/api/equipe?periodo=30')->json('linhas'))->keyBy('nome');

        $this->assertSame(['recebidas' => 3, 'concluidas' => 2, 'no_prazo' => '100%', 'tempo_medio' => '1,2 dias'],
            array_intersect_key($linhas['Ana Souza'], array_flip(['recebidas', 'concluidas', 'no_prazo', 'tempo_medio'])));
        $this->assertSame(1, $linhas['Diego Rocha']['atrasadas']);
        $this->assertSame(2, $linhas['Carla Mendes']['adiamentos']);
        $this->assertArrayNotHasKey('Henrique Duarte Pereira', $linhas->all());
    }

    public function test_texto_para_whatsapp(): void
    {
        $texto = $this->getJson('/api/relatorio-dia/texto?data=2026-10-28')->json('texto');

        $this->assertSame(rtrim(file_get_contents(base_path('tests/Fixtures/texto-whatsapp-exemplo.txt'))), $texto);
    }

    public function test_pdf_do_relatorio(): void
    {
        $carla = Usuario::query()->where('nome', 'Carla Mendes')->first();

        $this->get('/relatorio-dia/pdf?data=2026-10-28')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="relatorio-demandas-2026-10-28.pdf"');

        $resposta = $this->get("/relatorio-dia/pdf?data=2026-10-28&responsavel={$carla->id}");
        $this->assertStringStartsWith('%PDF', $resposta->getContent());
        $resposta->assertHeader('Content-Disposition', 'attachment; filename="relatorio-demandas-2026-10-28-carla-mendes.pdf"');
    }

    public function test_csv_com_bom_ponto_e_virgula_e_crlf(): void
    {
        $csv = $this->get('/demandas/export.csv')->assertOk()->streamedContent();
        $linhas = explode("\r\n", rtrim($csv, "\r\n"));

        $this->assertStringStartsWith("\u{FEFF}\"Entrada\";\"Cliente\";\"Descrição\"", $csv);
        $this->assertCount(11, $linhas);
        $this->assertStringEndsWith('"Etapas";"Ações realizadas";"Como foi finalizada";"Motivos de adiamento"', $linhas[0]);

        $clinica = collect($linhas)->first(fn ($l) => str_contains($l, 'Clínica Vida Plena'));
        $this->assertStringStartsWith(
            '"21/10/26 10:00";"Clínica Vida Plena";"Renovar o alvará de funcionamento junto à prefeitura.";"Ligação";"Legalização";"Carla Mendes";"21/10/26 10:20";"27/10/26 18:00";"29/10/26 08:00";"2";"Sim";"";"Adiada para 29/10/26 (08h00, 1º horário)";""',
            $clinica,
        );
    }
}
