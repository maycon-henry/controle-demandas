<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\Usuario;
use App\Notifications\ConviteAcesso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Seção 4 e casos 12 da 14.4 (T-04 a T-07, T-14). */
class AcessoEPermissoesTest extends TestCase
{
    use ComDadosDeExemplo, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->carregarExemplo();
    }

    public function test_login_com_mensagem_generica_e_bloqueio_apos_5_tentativas(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'ana@bitaraes.local', 'senha' => 'errada1'])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        }

        $this->post('/login', ['email' => 'ana@bitaraes.local', 'senha' => 'demanda123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_valido_e_rotas_protegidas(): void
    {
        $this->get('/relatorio')->assertRedirect('/login');
        $this->post('/login', ['email' => 'ANA@bitaraes.local', 'senha' => 'demanda123'])->assertRedirect('/relatorio');
        $this->assertAuthenticated();
        $this->get('/relatorio')->assertOk()->assertSee('Controle de Demandas');
    }

    public function test_usuario_inativo_nao_entra_e_perde_a_sessao(): void
    {
        $ana = $this->colaborador();
        $ana->update(['ativo' => false]);

        $this->post('/login', ['email' => 'ana@bitaraes.local', 'senha' => 'demanda123'])->assertSessionHasErrors('email');
        $this->actingAs($ana)->getJson('/api/sessao')->assertStatus(401)->assertJson(['message' => 'Sua sessão terminou. Entre novamente.']);
    }

    public function test_colaborador_nao_exclui_nem_gerencia_usuarios(): void
    {
        $t05 = $this->demanda('t05');
        $this->actingAs($this->colaborador());

        $this->deleteJson("/api/demandas/{$t05->id}")->assertStatus(403)->assertJson(['message' => 'Você não tem permissão para esta ação.']);
        $this->assertNull($t05->fresh()->excluido_em);
        $this->get('/usuarios')->assertForbidden();
        $this->getJson('/api/sessao')->assertJsonPath('usuario.gestor', false);
    }

    public function test_gestor_exclui_logicamente(): void
    {
        $t05 = $this->demanda('t05');
        $this->actingAs($this->gestor());

        $this->deleteJson("/api/demandas/{$t05->id}")->assertOk()->assertJson(['message' => 'Tarefa excluída']);

        $this->assertNotNull(Demanda::find($t05->id)->excluido_em);
        $this->assertSame('Excluída.', $t05->eventos()->where('tipo', 'excluida')->value('texto'));
        $this->assertSame(4, $this->getJson('/api/relatorio-dia?data=2026-10-28')->json('indicadores.entraram'));
        $this->getJson("/api/demandas/{$t05->id}")->assertNotFound();
    }

    public function test_gestao_de_usuarios_convite_e_ultimo_gestor(): void
    {
        Notification::fake();
        $gestor = $this->gestor();
        $this->actingAs($gestor);

        $this->post('/usuarios', ['nome' => 'Fernanda Dias', 'email' => 'fernanda@bitaraes.local', 'perfil' => 'colaborador', 'setor' => 'Fiscal'])
            ->assertRedirect('/usuarios');
        Notification::assertSentTo(Usuario::where('email', 'fernanda@bitaraes.local')->first(), ConviteAcesso::class);

        $this->post("/usuarios/{$gestor->id}/situacao")->assertSessionHasErrors(['situacao' => 'Não é possível remover o último gestor ativo.']);
        $this->assertTrue($gestor->fresh()->ativo);
    }

    public function test_senha_provisoria_obriga_troca_no_primeiro_acesso(): void
    {
        $this->actingAs($this->gestor())->post('/usuarios', [
            'nome' => 'Gustavo Reis', 'email' => 'gustavo@bitaraes.local', 'perfil' => 'colaborador', 'senha_provisoria' => 'provisoria1',
        ]);
        auth()->logout();

        $this->post('/login', ['email' => 'gustavo@bitaraes.local', 'senha' => 'provisoria1']);
        $this->get('/relatorio')->assertRedirect('/trocar-senha');
        $this->post('/trocar-senha', ['senha' => 'curta', 'senha_confirmation' => 'curta'])->assertSessionHasErrors('senha');
        $this->post('/trocar-senha', ['senha' => 'novaSenha2026', 'senha_confirmation' => 'novaSenha2026'])->assertRedirect('/relatorio');
        $this->get('/relatorio')->assertOk();
    }
}
