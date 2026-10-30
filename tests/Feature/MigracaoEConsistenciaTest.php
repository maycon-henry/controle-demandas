<?php

namespace Tests\Feature;

use App\Application\Migracao\ImportadorPrototipo;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Demandas\Status\TipoStatus;
use App\Domain\Shared\Fuso;
use App\Infrastructure\Persistencia\EscopoSituacaoSql;
use App\Infrastructure\Persistencia\FiltroSituacao;
use App\Infrastructure\Persistencia\MapeadorDemanda;
use App\Models\Demanda;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** T-22 (migração idempotente) e garantia de que os filtros SQL espelham as regras de status. */
class MigracaoEConsistenciaTest extends TestCase
{
    use ComDadosDeExemplo, RefreshDatabase;

    public function test_importacao_e_idempotente_e_cria_responsaveis_inativos(): void
    {
        $this->fixarRelogio();
        $gestor = Usuario::create(['nome' => 'Gestor', 'email' => 'g@bitaraes.local', 'senha_hash' => 'x', 'perfil' => 'gestor']);
        $docs = json_decode(file_get_contents(database_path('data/dados-exemplo.json')), true);
        $importador = app(ImportadorPrototipo::class);

        $primeira = $importador->importar($docs, $gestor);
        $segunda = $importador->importar($docs, $gestor);

        $this->assertSame(10, $primeira['importadas']);
        $this->assertSame(['importadas' => 0, 'ignoradas' => 10], array_intersect_key($segunda, array_flip(['importadas', 'ignoradas'])));
        $this->assertSame(10, Demanda::count());

        $ana = Usuario::where('nome', 'Ana Souza')->first();
        $this->assertFalse($ana->ativo);
        $this->assertSame('migrado+ana-souza@bitaraes.local', $ana->email);

        $t03 = Demanda::where('id_legado', 't03')->first();
        $this->assertSame('2026-10-29 08:00 -03:00', $t03->prazo->setTimezone(Fuso::TZ)->format('Y-m-d H:i P'));
        $this->assertSame(['criada', 'adiada', 'adiada'], $t03->eventos->pluck('tipo')->all());
        $this->assertSame(5, $this->actingAs($gestor->fresh())->getJson('/api/relatorio-dia?data=2026-10-28')->json('indicadores.entraram'));
    }

    public static function momentos(): array
    {
        return [['2026-10-28 19:30'], ['2026-10-29 08:15'], ['2026-10-29 10:00'], ['2026-10-26 09:00']];
    }

    #[DataProvider('momentos')]
    public function test_filtros_sql_concordam_com_o_dominio(string $momento): void
    {
        $this->carregarExemplo();
        $agora = Fuso::parse($momento);
        $calc = new CalculadoraStatus;
        $mapeador = new MapeadorDemanda;

        $statusPorId = Demanda::with('adiamentos')->get()
            ->mapWithKeys(fn ($m) => [$m->id => $calc->calcular($mapeador->paraDados($m), $agora)]);

        $esperado = [
            'concluida' => fn ($s) => $s->grupo()->value === 'concluida',
            'pendente' => fn ($s) => $s->grupo()->value === 'pendente',
            'adiada' => fn ($s) => $s->grupo()->value === 'adiada',
            'atrasada' => fn ($s) => $s->tipo === TipoStatus::Atrasada,
            'vence_hoje' => fn ($s) => $s->tipo === TipoStatus::VenceHoje,
        ];

        foreach ($esperado as $filtro => $criterio) {
            $sql = app(EscopoSituacaoSql::class)->aplicar(Demanda::query(), FiltroSituacao::from($filtro), $agora)->pluck('id')->sort()->values()->all();
            $dominio = $statusPorId->filter($criterio)->keys()->sort()->values()->all();
            $this->assertSame($dominio, $sql, "Filtro $filtro em $momento");
        }
    }
}
