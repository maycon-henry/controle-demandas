<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Demanda;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Formulário, adiar, finalizar, reabrir, etapas, ações e concorrência (seções 6.3 a 6.6, 14.4). */
class CicloDaDemandaTest extends TestCase
{
    use ComDadosDeExemplo, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->carregarExemplo();
        $this->actingAs($this->colaborador('bruno@bitaraes.local'));
    }

    private function nova(array $campos = []): Demanda
    {
        $id = $this->postJson('/api/demandas', [
            'entrada' => '2026-10-28T19:00', 'cliente' => 'Padaria Sol Nascente', 'descricao' => 'Enviar o informe de rendimentos.',
            'canal' => 'WhatsApp', 'setor' => 'Pessoal', 'responsavel_id' => $this->colaborador('bruno@bitaraes.local')->id,
            'acordo_em' => '2026-10-28T19:20', 'prazo' => '2026-10-30T12:00', 'etapas' => "Gerar informes\nConferir valores\nEnviar ao cliente",
            ...$campos,
        ])->assertCreated()->assertJson(['message' => 'Tarefa registrada'])->json('id');

        return Demanda::findOrFail($id);
    }

    public function test_criar_registra_evento_cliente_existente_e_etapas(): void
    {
        $d = $this->nova();

        $this->assertSame(1, Cliente::query()->where('nome', 'Padaria Sol Nascente')->count());
        $this->assertSame(['Gerar informes', 'Conferir valores', 'Enviar ao cliente'], $d->etapas->pluck('texto')->all());
        $this->assertSame('Tarefa lançada (WhatsApp) para Bruno Alves.', $d->eventos->first()->texto);
        $this->assertSame($this->colaborador('bruno@bitaraes.local')->id, $d->eventos->first()->usuario_id);
    }

    public function test_campos_obrigatorios_listam_so_os_que_faltam(): void
    {
        $this->postJson('/api/demandas', ['entrada' => '2026-10-28T19:00'])
            ->assertStatus(422)->assertJsonPath('errors.formulario.0', 'Preencha o cliente e a descrição.');
        $this->postJson('/api/demandas', ['cliente' => 'X'])
            ->assertJsonPath('errors.formulario.0', 'Preencha o dia e hora de entrada e a descrição.');
    }

    public function test_canal_outro_grava_o_texto_digitado(): void
    {
        $this->assertSame('Instagram', $this->nova(['canal' => 'Outro', 'canal_outro' => 'Instagram'])->canal);
        $this->assertSame('Outro', $this->nova(['canal' => 'Outro', 'canal_outro' => ''])->canal);
    }

    public function test_adiar_registra_adiamento_evento_e_primeiro_horario(): void
    {
        $d = $this->nova();

        $this->postJson("/api/demandas/{$d->id}/adiar", ['para' => '2026-10-31T08:00', 'motivo' => 'Sistema da prefeitura fora do ar', 'primeiro_horario' => true])
            ->assertOk()->assertJson(['message' => 'Adiamento registrado']);

        $d->refresh();
        $this->assertTrue($d->primeiro_horario);
        $this->assertSame('31/10/26 08h00', $d->prazo->format('d/m/y H\hi'));
        $this->assertSame('Adiada de 30/10/26 12h00 para 31/10/26 08h00 (primeiro horário). Motivo: Sistema da prefeitura fora do ar.',
            $d->eventos()->where('tipo', Evento::ADIADA)->value('texto'));
        $this->postJson("/api/demandas/{$d->id}/adiar", [])->assertJsonPath('errors.para.0', 'Informe o novo prazo.');
    }

    public function test_editar_prazo_para_depois_cria_adiamento_e_para_antes_nao(): void
    {
        $d = $this->nova();
        $base = ['entrada' => '2026-10-28T19:00', 'cliente' => 'Padaria Sol Nascente', 'descricao' => 'Enviar o informe de rendimentos.',
            'canal' => 'WhatsApp', 'responsavel_id' => $d->responsavel_id];

        $this->patchJson("/api/demandas/{$d->id}", [...$base, 'prazo' => '2026-11-03T12:00', 'versao' => 1])->assertOk();
        $this->assertSame(1, $d->adiamentos()->count());
        $this->assertNull($d->adiamentos()->value('motivo'));

        $this->patchJson("/api/demandas/{$d->id}", [...$base, 'prazo' => '2026-10-29T12:00', 'versao' => 2])->assertOk();
        $this->assertSame(1, $d->adiamentos()->count());
        $this->assertDatabaseHas('eventos', [
            'demanda_id' => $d->id,
            'tipo' => Evento::EDITADA,
            'texto' => 'Editada: prazo de 03/11/26 12h00 para 29/10/26 12h00.',
        ]);
    }

    public function test_conflito_de_edicao_nao_sobrescreve(): void
    {
        $d = $this->nova();
        $base = ['entrada' => '2026-10-28T19:00', 'cliente' => 'Padaria Sol Nascente', 'canal' => 'WhatsApp'];

        $this->patchJson("/api/demandas/{$d->id}", [...$base, 'descricao' => 'Primeira pessoa', 'versao' => 1])->assertOk();
        $this->patchJson("/api/demandas/{$d->id}", [...$base, 'descricao' => 'Segunda pessoa', 'versao' => 1])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta tarefa foi alterada por outra pessoa. Recarregamos os dados; confira e salve de novo.');

        $this->assertSame('Primeira pessoa', $d->fresh()->descricao);
    }

    public function test_finalizar_valida_texto_data_e_marca_etapas_restantes(): void
    {
        $d = $this->nova();

        $this->postJson("/api/demandas/{$d->id}/finalizar", ['fim' => '2026-10-28T19:30'])
            ->assertJsonPath('errors.conclusao.0', 'Descreva como a tarefa foi finalizada.');
        $this->postJson("/api/demandas/{$d->id}/finalizar", ['fim' => '2026-10-27T10:00', 'conclusao' => 'x'])
            ->assertStatus(422)->assertJsonPath('message', 'A data de finalização está antes da entrada. Confira os horários.');

        $this->postJson("/api/demandas/{$d->id}/finalizar", ['fim' => '2026-10-28T19:30', 'conclusao' => 'Informes enviados.', 'marcar_etapas' => true])
            ->assertJson(['message' => 'Tarefa concluída']);

        $d->refresh();
        $this->assertTrue($d->etapas->every(fn ($e) => $e->feita && $e->feita_em->eq($d->fim)));
        $this->assertSame('Finalizada: Informes enviados.', $d->eventos()->where('tipo', Evento::FINALIZADA)->value('texto'));
    }

    public function test_reabrir_com_prazo_vencido_volta_a_atrasada_e_mantem_conclusao(): void
    {
        $t01 = $this->demanda('t01');

        $this->postJson("/api/demandas/{$t01->id}/reabrir")->assertJson(['message' => 'Tarefa reaberta']);
        $json = $this->getJson("/api/demandas/{$t01->id}")->json();

        $this->assertSame('atrasada', $json['status']['complemento']);
        $this->assertSame('PGDAS transmitido, recibo e DAS enviados ao cliente por e-mail às 11h20.', $json['conclusao']);
        $this->assertSame('Reaberta.', last($json['historico'])['texto']);
        $this->assertSame('Henrique Duarte Pereira', $json['historico'][0]['por']);
    }

    public function test_etapas_e_acoes_pelo_painel_detalhes(): void
    {
        $t03 = $this->demanda('t03');

        $this->postJson("/api/demandas/{$t03->id}/etapas", ['texto' => 'Pagar taxa'])->assertJson(['message' => 'Etapa adicionada']);
        $etapa = $t03->etapas()->where('texto', 'Preencher requerimento')->first();
        $this->patchJson("/api/etapas/{$etapa->id}", ['feita' => true])->assertOk();
        $this->postJson("/api/demandas/{$t03->id}/acoes", ['em' => '2026-10-28T18:00', 'texto' => ''])
            ->assertJsonPath('errors.texto.0', 'Descreva a ação realizada.');
        $this->postJson("/api/demandas/{$t03->id}/acoes", ['em' => '2026-10-28T18:00', 'texto' => 'Requerimento preenchido.'])
            ->assertJson(['message' => 'Ação registrada']);

        $json = $this->getJson("/api/demandas/{$t03->id}")->json();
        $this->assertSame(3, $json['etapas_feitas']);
        $this->assertCount(5, $json['etapas']);
        $this->assertSame('Requerimento preenchido.', last($json['acoes'])['texto']);
        $this->assertSame('Etapa concluída: Preencher requerimento', last($json['historico'])['texto']);
        $this->assertSame('Bruno Alves', last($json['historico'])['por']);
    }

    public function test_reconciliacao_de_etapas_pelo_formulario(): void
    {
        $t03 = $this->demanda('t03');
        $base = ['entrada' => '2026-10-21T10:00', 'cliente' => 'Clínica Vida Plena', 'descricao' => 'Renovar o alvará.', 'canal' => 'Ligação',
            'responsavel_id' => $t03->responsavel_id, 'prazo' => '2026-10-29T08:00', 'versao' => $t03->versao];

        $this->patchJson("/api/demandas/{$t03->id}", [...$base, 'etapas' => "Emitir certidões negativas\nNova etapa"])->assertOk();

        $etapas = $t03->etapas()->get();
        $this->assertSame(['Emitir certidões negativas', 'Nova etapa'], $etapas->pluck('texto')->all());
        $this->assertTrue($etapas[0]->feita);
        $this->assertFalse($etapas[1]->feita);
    }
}
