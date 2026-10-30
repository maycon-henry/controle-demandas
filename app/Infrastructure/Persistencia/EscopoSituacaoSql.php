<?php

namespace App\Infrastructure\Persistencia;

use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Tradução para SQL dos grupos de status da seção 6.1, necessária para filtrar e paginar
 * no banco. Espelha App\Domain\Demandas\Status\CalculadoraStatus; o teste
 * EscopoSituacaoSqlTest garante que os dois concordam.
 */
final class EscopoSituacaoSql
{
    public function aplicar(Builder $q, FiltroSituacao $filtro, CarbonImmutable $agora): Builder
    {
        return match ($filtro) {
            FiltroSituacao::Todas => $q,
            FiltroSituacao::Concluidas => $q->whereNotNull('demandas.fim'),
            FiltroSituacao::Atrasadas => $this->atrasadas($q, $agora),
            FiltroSituacao::Adiadas => $this->abertasComPrazoFuturo($q, $agora)->whereExists($this->temAdiamento(...)),
            FiltroSituacao::Pendentes => $q->whereNull('demandas.fim')->where(fn (Builder $w) => $w
                ->whereNull('demandas.prazo')
                ->orWhere('demandas.prazo', '<', $agora)
                ->orWhereNotExists($this->temAdiamento(...))),
            FiltroSituacao::VencemHoje => $this->abertasComPrazoFuturo($q, $agora)
                ->whereNotExists($this->temAdiamento(...))
                ->where('demandas.prazo', '<', Fuso::inicioDoDia(Fuso::somarDias(Fuso::dia($agora), 1))),
        };
    }

    public function atrasadas(Builder $q, CarbonImmutable $agora): Builder
    {
        return $q->whereNull('demandas.fim')->where('demandas.prazo', '<', $agora);
    }

    private function abertasComPrazoFuturo(Builder $q, CarbonImmutable $agora): Builder
    {
        return $q->whereNull('demandas.fim')->where('demandas.prazo', '>=', $agora);
    }

    private function temAdiamento(QueryBuilder $sub): void
    {
        $sub->selectRaw('1')->from('adiamentos')->whereColumn('adiamentos.demanda_id', 'demandas.id');
    }
}
