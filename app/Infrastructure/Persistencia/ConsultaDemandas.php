<?php

namespace App\Infrastructure\Persistencia;

use App\Domain\Shared\Fuso;
use App\Models\Demanda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/** Consultas de leitura das demandas (não excluídas). */
final class ConsultaDemandas
{
    public const VALOR_SEM_RESPONSAVEL = 'sem';

    private const RELACOES_COMPLETAS = [
        'cliente', 'responsavel', 'adiamentos', 'etapas', 'acoes.usuario', 'eventos.usuario',
    ];

    public function __construct(private readonly EscopoSituacaoSql $situacao) {}

    public function completa(): Builder
    {
        return Demanda::query()->ativas()->with(self::RELACOES_COMPLETAS);
    }

    public function encontrar(string $id): Demanda
    {
        return $this->completa()->findOrFail($id);
    }

    /**
     * Pré-seleção no banco das demandas que podem aparecer no relatório do dia D
     * (as regras exatas são aplicadas depois, no domínio).
     */
    public function candidatasRelatorio(string $dia): Collection
    {
        $inicio = Fuso::inicioDoDia($dia);
        $fimAmanha = Fuso::inicioDoDia(Fuso::somarDias($dia, 2));
        $fimDia = Fuso::inicioDoDia(Fuso::somarDias($dia, 1));

        return $this->completa()->where(function (Builder $q) use ($inicio, $fimDia, $fimAmanha) {
            $q->whereBetween('entrada', [$inicio, $fimDia])
                ->orWhereBetween('fim', [$inicio, $fimDia])
                ->orWhereBetween('prazo', [$inicio, $fimAmanha])
                ->orWhere(fn (Builder $w) => $w->whereNull('fim')->where('prazo', '<', $inicio))
                ->orWhereExists(fn (QueryBuilder $s) => $this->filhos($s, 'adiamentos')->where(fn ($w) => $w
                    ->whereBetween('de', [$inicio, $fimAmanha])->orWhereBetween('em', [$inicio, $fimDia])))
                ->orWhereExists(fn (QueryBuilder $s) => $this->filhos($s, 'acoes')->whereBetween('em', [$inicio, $fimDia]))
                ->orWhereExists(fn (QueryBuilder $s) => $this->filhos($s, 'etapas')->whereBetween('feita_em', [$inicio, $fimDia]));
        })->get();
    }

    /** Demandas com o mínimo necessário para a aba Equipe. */
    public function paraDesempenho(): Collection
    {
        return Demanda::query()->ativas()->with(['responsavel', 'adiamentos'])->get();
    }

    /** Página da aba "Todas as tarefas": não finalizadas primeiro, entrada mais recente primeiro. */
    public function listar(FiltrosLista $filtros, CarbonImmutable $agora, int $limite, int $deslocamento = 0): array
    {
        $q = $this->filtrada($filtros, $agora);
        $total = (clone $q)->count();

        $itens = $q->with(self::RELACOES_COMPLETAS)
            ->orderByRaw('(demandas.fim IS NULL) DESC')
            ->orderByDesc('demandas.entrada')
            ->orderBy('demandas.id')
            ->offset($deslocamento)
            ->limit($limite)
            ->get();

        return [$itens, $total];
    }

    /** @return array{total: int, pendentes: int, adiadas: int, atrasadas: int, concluidas: int, no_prazo: int, duracao_media: ?float} */
    public function indicadoresLista(CarbonImmutable $agora): array
    {
        $base = fn () => Demanda::query()->ativas();

        $concluidas = $base()->whereNotNull('fim')->selectRaw(
            'count(*) AS total, '.
            'count(*) FILTER (WHERE prazo IS NULL OR fim <= prazo) AS no_prazo, '.
            'avg(extract(epoch FROM fim - entrada)) AS duracao'
        )->toBase()->first();

        return [
            'total' => $base()->count(),
            'pendentes' => $this->situacao->aplicar($base(), FiltroSituacao::Pendentes, $agora)->count(),
            'adiadas' => $this->situacao->aplicar($base(), FiltroSituacao::Adiadas, $agora)->count(),
            'atrasadas' => $this->situacao->aplicar($base(), FiltroSituacao::Atrasadas, $agora)->count(),
            'concluidas' => (int) $concluidas->total,
            'no_prazo' => (int) $concluidas->no_prazo,
            'duracao_media' => $concluidas->duracao !== null ? (float) $concluidas->duracao : null,
        ];
    }

    /** Assinatura que muda sempre que alguma demanda é gravada (tempo real por consulta). */
    public function assinatura(): string
    {
        $linha = DB::table('demandas')->selectRaw('count(*) AS n, max(atualizado_em) AS m, sum(versao) AS v')->first();

        return md5($linha->n.'|'.$linha->m.'|'.$linha->v);
    }

    private function filtrada(FiltrosLista $f, CarbonImmutable $agora): Builder
    {
        $q = Demanda::query()->ativas()->select('demandas.*');
        $this->situacao->aplicar($q, $f->situacao, $agora);

        if ($f->canal !== '') {
            $q->where('canal', $f->canal);
        }
        if ($f->setor !== '') {
            $q->where('setor', $f->setor);
        }
        if ($f->responsavel === self::VALOR_SEM_RESPONSAVEL) {
            $q->whereNull('responsavel_id');
        } elseif ($f->responsavel !== '') {
            $q->where('responsavel_id', $f->responsavel);
        }
        if ($f->busca !== '') {
            $this->buscar($q, $f->busca);
        }

        return $q;
    }

    /** Busca em cliente, descrição, responsável e observações, sem diferenciar maiúsculas e acentos. */
    private function buscar(Builder $q, string $termo): void
    {
        $padrao = '%'.addcslashes(mb_strtolower($termo), '%_\\').'%';
        $campo = fn (string $sql) => "unaccent(lower(coalesce($sql, ''))) LIKE unaccent(?)";

        $q->where(fn (Builder $w) => $w
            ->whereRaw($campo('demandas.descricao'), [$padrao])
            ->orWhereRaw($campo('demandas.observacoes'), [$padrao])
            ->orWhereExists(fn (QueryBuilder $s) => $s->selectRaw('1')->from('clientes')
                ->whereColumn('clientes.id', 'demandas.cliente_id')->whereRaw($campo('clientes.nome'), [$padrao]))
            ->orWhereExists(fn (QueryBuilder $s) => $s->selectRaw('1')->from('usuarios')
                ->whereColumn('usuarios.id', 'demandas.responsavel_id')->whereRaw($campo('usuarios.nome'), [$padrao])));
    }

    private function filhos(QueryBuilder $sub, string $tabela): QueryBuilder
    {
        return $sub->selectRaw('1')->from($tabela)->whereColumn("$tabela.demanda_id", 'demandas.id');
    }
}
