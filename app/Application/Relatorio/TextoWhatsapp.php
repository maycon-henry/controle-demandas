<?php

namespace App\Application\Relatorio;

use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Relatorio\RelatorioDia;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Texto;

/** Texto do botão "Copiar relatório", com negrito do WhatsApp (seção 8.3). */
final class TextoWhatsapp
{
    private const LIMITE_DESCRICAO = 140;

    private const RECUO = '   ';

    public function __construct(private readonly AnalisePrazos $prazos = new AnalisePrazos) {}

    public function gerar(RelatorioDia $r, ?string $nomeFiltro): string
    {
        $mostrarResponsavel = ! $r->filtrado();
        $cabecalho = 'Relatório de demandas – '.Formatador::extenso($r->dia).($nomeFiltro ? ' · '.$nomeFiltro : '');

        $blocos = [
            '*'.$cabecalho.'*',
            $this->secao('Feito', $r->feitas, fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel)
                .' · finalizada '.Formatador::hora($t->fim)
                .($t->conclusao ? "\n".self::RECUO.'Como: '.Texto::resumir($t->conclusao, 500) : '')),
            $this->secao('Em andamento', $r->emAndamento, fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel)
                .$this->atividadesDoDia($t, $r->dia)),
            $this->secao($r->ehHoje ? 'Era para hoje e ficou para depois' : 'Era para este dia e ficou para depois', $r->ficouParaDepois,
                fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel).$this->novoPrazo($t, $r->dia)),
            $this->secao('Primeiro horário de amanhã', $r->primeiroHorario, fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel)),
            $this->secao('Demais previstas para amanhã', $r->demaisAmanha, fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel)
                .' · prazo '.Formatador::hora($t->prazo)),
            $this->secao('Demandas novas', $r->novas, fn (DemandaDados $t) => $this->item($t, $mostrarResponsavel)
                .' · '.$t->canal.', '.Formatador::hora($t->entrada)),
        ];

        return implode("\n\n", $blocos);
    }

    /** @param  list<DemandaDados>  $itens */
    private function secao(string $titulo, array $itens, callable $linha): string
    {
        $corpo = $itens === [] ? '—' : implode("\n", array_map($linha, $itens));

        return sprintf("*%s (%d)*\n%s", $titulo, count($itens), $corpo);
    }

    private function item(DemandaDados $t, bool $mostrarResponsavel): string
    {
        $texto = '• '.$t->cliente.' – '.Texto::resumir($t->descricao, self::LIMITE_DESCRICAO);

        return $mostrarResponsavel ? $texto.' ('.$t->nomeResponsavel().')' : $texto;
    }

    private function atividadesDoDia(DemandaDados $t, string $dia): string
    {
        $linhas = [];
        foreach ($t->acoes as $acao) {
            if (Fuso::dia($acao->em) === $dia) {
                $linhas[] = self::RECUO.Formatador::hora($acao->em).' '.Texto::resumir($acao->texto, self::LIMITE_DESCRICAO);
            }
        }

        if ($linhas === []) {
            foreach ($t->etapas as $etapa) {
                if ($etapa->feita && Fuso::dia($etapa->feitaEm) === $dia) {
                    $linhas[] = self::RECUO.Formatador::hora($etapa->feitaEm).' Etapa concluída: '.$etapa->texto;
                }
            }
        }

        return $linhas === [] ? '' : "\n".implode("\n", $linhas);
    }

    private function novoPrazo(DemandaDados $t, string $dia): string
    {
        $depois = $this->prazos->prazoDepois($t, $dia);

        return $depois && Fuso::dia($depois) !== $dia
            ? ' · novo prazo '.Formatador::dataHora($depois)
            : ' · ainda sem novo prazo';
    }
}
