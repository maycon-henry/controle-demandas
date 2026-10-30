<?php

namespace App\Domain\Equipe;

use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Demandas\Status\TipoStatus;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Texto;
use Carbon\CarbonImmutable;

/** Tabela "Desempenho por colaborador" (seção 6.7). */
final class CalculadoraDesempenho
{
    private const SEM_RESPONSAVEL = '';

    public function __construct(private readonly CalculadoraStatus $status = new CalculadoraStatus) {}

    /**
     * @param  iterable<DemandaDados>  $demandas
     * @param  array<string, array{nome: string, ativo: bool}>  $pessoas  colaboradores que sempre aparecem
     * @return list<LinhaDesempenho>
     */
    public function calcular(iterable $demandas, array $pessoas, Periodo $periodo, CarbonImmutable $agora): array
    {
        $acumulado = [];
        foreach ($pessoas as $id => $pessoa) {
            $acumulado[$id] = $this->linhaVazia($id, $pessoa['nome'], $pessoa['ativo']);
        }

        foreach ($demandas as $t) {
            $chave = $t->responsavelId ?? self::SEM_RESPONSAVEL;
            $acumulado[$chave] ??= $this->linhaVazia($t->responsavelId, $t->nomeResponsavel(), true);
            $this->somar($acumulado[$chave], $t, $periodo, $agora);
        }

        $linhas = array_map(fn (array $a) => new LinhaDesempenho(
            responsavelId: $a['id'],
            nome: $a['nome'],
            ativo: $a['ativo'],
            recebidas: $a['recebidas'],
            concluidas: $a['concluidas'],
            noPrazoPct: $a['concluidas'] > 0 ? (int) round(100 * $a['no_prazo'] / $a['concluidas']) : null,
            adiamentos: $a['adiamentos'],
            tempoMedioSegundos: $a['concluidas'] > 0 ? $a['duracao_total'] / $a['concluidas'] : null,
            emAberto: $a['em_aberto'],
            atrasadas: $a['atrasadas'],
        ), array_values($acumulado));

        usort($linhas, fn (LinhaDesempenho $a, LinhaDesempenho $b) => Texto::compararNomes($a->nome, $b->nome));

        return $linhas;
    }

    private function somar(array &$a, DemandaDados $t, Periodo $periodo, CarbonImmutable $agora): void
    {
        if ($periodo->contem(Fuso::dia($t->entrada), $agora)) {
            $a['recebidas']++;
        }

        $status = $this->status->calcular($t, $agora);

        if ($t->fim !== null && $periodo->contem(Fuso::dia($t->fim), $agora)) {
            $a['concluidas']++;
            $a['duracao_total'] += $t->fim->getTimestamp() - $t->entrada->getTimestamp();
            if ($status->tipo === TipoStatus::ConcluidaNoPrazo) {
                $a['no_prazo']++;
            }
        }

        foreach ($t->adiamentos as $adiamento) {
            if ($periodo->contem(Fuso::dia($adiamento->em), $agora)) {
                $a['adiamentos']++;
            }
        }

        if ($t->fim === null) {
            $a['em_aberto']++;
        }

        if ($status->tipo === TipoStatus::Atrasada) {
            $a['atrasadas']++;
        }
    }

    private function linhaVazia(?string $id, string $nome, bool $ativo): array
    {
        return [
            'id' => $id, 'nome' => $nome, 'ativo' => $ativo,
            'recebidas' => 0, 'concluidas' => 0, 'no_prazo' => 0, 'adiamentos' => 0,
            'duracao_total' => 0, 'em_aberto' => 0, 'atrasadas' => 0,
        ];
    }
}
