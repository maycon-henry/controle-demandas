<?php

namespace App\Domain\Relatorio;

use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Shared\Fuso;
use Carbon\CarbonImmutable;

/** Monta o relatório de um dia D a partir das regras da seção 6.2. */
final class GeradorRelatorioDia
{
    public function __construct(private readonly AnalisePrazos $prazos = new AnalisePrazos) {}

    /**
     * @param  iterable<DemandaDados>  $demandas  demandas não excluídas (pode ser um superconjunto)
     * @param  string|null  $responsavelId  filtro de colaborador; null = toda a equipe
     */
    public function gerar(iterable $demandas, string $dia, ?string $responsavelId, CarbonImmutable $agora): RelatorioDia
    {
        $lista = [];
        foreach ($demandas as $t) {
            if ($responsavelId === null || $t->responsavelId === $responsavelId) {
                $lista[] = $t;
            }
        }

        $amanha = Fuso::somarDias($dia, 1);
        $ehHoje = $dia === Fuso::dia($agora);

        $feitas = $this->ordenar($this->filtrar($lista, fn ($t) => Fuso::dia($t->fim) === $dia), 'fim');

        $emAndamento = $this->ordenar($this->filtrar($lista, fn ($t) => Fuso::dia($t->fim) !== $dia && $this->teveAtividadeNoDia($t, $dia)), 'entrada');

        $ficou = $this->ordenar($this->filtrar($lista, fn ($t) => $this->prazos->pendenteNoDia($t, $dia)), 'entrada');

        $listaAmanha = $this->filtrar($lista, fn ($t) => $this->previstaPara($t, $amanha));
        $primeiroHorario = $this->ordenar($this->filtrar($listaAmanha, fn ($t) => $t->primeiroHorario), 'prazo');
        $demais = $this->ordenar($this->filtrar($listaAmanha, fn ($t) => ! $t->primeiroHorario), 'prazo');

        $novas = $this->ordenar($this->filtrar($lista, fn ($t) => Fuso::dia($t->entrada) === $dia), 'entrada');

        $atrasadas = $ehHoje
            ? $this->ordenar($this->filtrar($lista, fn ($t) => $t->fim === null && $t->prazo !== null && Fuso::dia($t->prazo) < $dia), 'entrada')
            : [];

        $adiadasNoDia = $this->ordenar($this->filtrar($lista, fn ($t) => $this->foiAdiadaNoDia($t, $dia)), 'entrada');

        $feitasPrevistas = $this->filtrar($feitas, fn ($t) => $this->prazos->tinhaPrazoNoDia($t, $dia));
        $previstas = [];
        foreach ([...$ficou, ...$feitasPrevistas] as $t) {
            $previstas[$t->id] = true;
        }

        return new RelatorioDia(
            dia: $dia,
            amanha: $amanha,
            ehHoje: $ehHoje,
            responsavelId: $responsavelId,
            feitas: $feitas,
            emAndamento: $emAndamento,
            ficouParaDepois: $ficou,
            primeiroHorario: $primeiroHorario,
            demaisAmanha: $demais,
            novas: $novas,
            atrasadasAnteriores: $atrasadas,
            adiadasNoDia: $adiadasNoDia,
            previstas: count($previstas),
            cumpridas: count($feitasPrevistas),
        );
    }

    /** Lista "amanhã(D)" da seção 6.2, avaliada para N = D + 1. */
    private function previstaPara(DemandaDados $t, string $n): bool
    {
        if ($t->fim !== null && Fuso::dia($t->fim) < $n) {
            return false;
        }

        return $this->prazos->tinhaPrazoNoDia($t, $n)
            && (Fuso::dia($t->prazo) === $n || $this->prazos->pendenteNoDia($t, $n));
    }

    private function teveAtividadeNoDia(DemandaDados $t, string $dia): bool
    {
        foreach ($t->acoes as $acao) {
            if (Fuso::dia($acao->em) === $dia) {
                return true;
            }
        }

        foreach ($t->etapas as $etapa) {
            if ($etapa->feita && Fuso::dia($etapa->feitaEm) === $dia) {
                return true;
            }
        }

        return false;
    }

    private function foiAdiadaNoDia(DemandaDados $t, string $dia): bool
    {
        foreach ($t->adiamentos as $adiamento) {
            if (Fuso::dia($adiamento->em) === $dia) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<DemandaDados>  $lista
     * @return list<DemandaDados>
     */
    private function filtrar(array $lista, callable $criterio): array
    {
        return array_values(array_filter($lista, $criterio));
    }

    /**
     * Ordena por um campo de data crescente; nulos por último, desempate pela entrada.
     *
     * @param  list<DemandaDados>  $lista
     * @return list<DemandaDados>
     */
    private function ordenar(array $lista, string $campo): array
    {
        usort($lista, function (DemandaDados $a, DemandaDados $b) use ($campo) {
            $va = $a->{$campo};
            $vb = $b->{$campo};

            if ($va === null || $vb === null) {
                return ($va === null) <=> ($vb === null);
            }

            return [$va, $a->entrada, $a->id] <=> [$vb, $b->entrada, $b->id];
        });

        return $lista;
    }
}
