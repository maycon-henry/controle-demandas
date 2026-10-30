<?php

namespace App\Application\Migracao;

use App\Application\Demandas\ResolvedorCliente;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Texto;
use App\Models\Acao;
use App\Models\Adiamento;
use App\Models\Demanda;
use App\Models\Etapa;
use App\Models\Evento;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa a exportação JSON do protótipo (seção 12). Idempotente: demandas cujo
 * id_legado já existe são ignoradas, então rodar duas vezes não duplica.
 */
final class ImportadorPrototipo
{
    public function __construct(private readonly ResolvedorCliente $clientes) {}

    /**
     * @param  list<array<string, mixed>>  $documentos
     * @return array{importadas: int, ignoradas: int, usuarios_criados: list<string>}
     */
    public function importar(array $documentos, Usuario $gestor): array
    {
        $resultado = ['importadas' => 0, 'ignoradas' => 0, 'usuarios_criados' => []];

        foreach ($documentos as $doc) {
            DB::transaction(function () use ($doc, $gestor, &$resultado) {
                if (Demanda::query()->where('id_legado', $doc['id'])->exists()) {
                    $resultado['ignoradas']++;

                    return;
                }

                $this->importarDemanda($doc, $gestor, $resultado['usuarios_criados']);
                $resultado['importadas']++;
            });
        }

        return $resultado;
    }

    /** @param  list<string>  $usuariosCriados */
    private function importarDemanda(array $doc, Usuario $gestor, array &$usuariosCriados): void
    {
        $demanda = new Demanda([
            'id_legado' => $doc['id'],
            'entrada' => $this->data($doc['entrada']),
            'cliente_id' => $this->clientes->resolver($doc['cliente'])->id,
            'descricao' => $doc['descricao'],
            'canal' => $doc['canal'] ?: 'Outro',
            'setor' => $doc['setor'] ?: 'Outro',
            'responsavel_id' => $this->responsavel($doc['responsavel'] ?? '', $usuariosCriados)?->id,
            'acordo_em' => $this->data($doc['acordoEm'] ?? ''),
            'prazo' => $this->data($doc['prazo'] ?? ''),
            'primeiro_horario' => (bool) ($doc['primeiroHorario'] ?? false),
            'fim' => $this->data($doc['fim'] ?? ''),
            'conclusao' => ($doc['conclusao'] ?? '') ?: null,
            'observacoes' => ($doc['obs'] ?? '') ?: null,
            'criado_por' => $gestor->id,
        ]);
        $demanda->timestamps = false;
        $demanda->criado_em = isset($doc['criadoEm']) ? CarbonImmutable::parse($doc['criadoEm']) : $demanda->entrada;
        $demanda->atualizado_em = isset($doc['atualizadoEm']) ? CarbonImmutable::parse($doc['atualizadoEm']) : $demanda->entrada;
        $demanda->save();

        foreach ($doc['adiamentos'] ?? [] as $a) {
            Adiamento::create([
                'demanda_id' => $demanda->id, 'de' => $this->data($a['de']), 'para' => $this->data($a['para']),
                'em' => $this->data($a['em']), 'motivo' => ($a['motivo'] ?? '') ?: null, 'usuario_id' => $gestor->id,
            ]);
        }

        foreach (array_values($doc['etapas'] ?? []) as $i => $e) {
            Etapa::create([
                'demanda_id' => $demanda->id, 'ordem' => $i + 1, 'texto' => $e['txt'], 'feita' => (bool) $e['feita'],
                'feita_em' => $e['feita'] ? $this->data($e['em'] ?? '') : null, 'usuario_id' => $e['feita'] ? $gestor->id : null,
            ]);
        }

        foreach ($doc['acoes'] ?? [] as $a) {
            Acao::create(['demanda_id' => $demanda->id, 'em' => $this->data($a['em']), 'texto' => $a['txt'], 'usuario_id' => $gestor->id]);
        }

        foreach ($doc['eventos'] ?? [] as $ev) {
            Evento::create([
                'demanda_id' => $demanda->id, 'em' => $this->data($ev['em']), 'texto' => $ev['txt'],
                'tipo' => $this->tipoEvento($ev['txt']), 'usuario_id' => $gestor->id,
            ]);
        }
    }

    /** "AAAA-MM-DDTHH:MM" em horário de Brasília (sem fuso); texto vazio vira nulo. */
    private function data(?string $valor): ?CarbonImmutable
    {
        return $valor === null || trim($valor) === '' ? null : CarbonImmutable::parse($valor, Fuso::TZ);
    }

    /** @param  list<string>  $usuariosCriados */
    private function responsavel(string $nome, array &$usuariosCriados): ?Usuario
    {
        $nome = trim($nome);
        if ($nome === '') {
            return null;
        }

        $existente = Usuario::query()->where('nome', $nome)->orderByDesc('ativo')->first();
        if ($existente) {
            return $existente;
        }

        $usuariosCriados[] = $nome;

        return Usuario::create([
            'nome' => $nome,
            'email' => 'migrado+'.Texto::slug($nome).'@bitaraes.local',
            'senha_hash' => Str::random(40),
            'perfil' => Usuario::PERFIL_COLABORADOR,
            'ativo' => false,
        ]);
    }

    private function tipoEvento(string $texto): string
    {
        return match (true) {
            str_starts_with($texto, 'Tarefa lançada') => Evento::CRIADA,
            str_starts_with($texto, 'Adiada') => Evento::ADIADA,
            str_starts_with($texto, 'Finalizada') => Evento::FINALIZADA,
            str_starts_with($texto, 'Reaberta') => Evento::REABERTA,
            str_starts_with($texto, 'Etapa') => Evento::ETAPA,
            default => Evento::EDITADA,
        };
    }
}
