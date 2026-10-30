<?php

namespace App\Application\Exportacao;

use App\Domain\Demandas\AnalisePrazos;
use App\Domain\Demandas\DemandaDados;
use App\Domain\Demandas\Status\CalculadoraStatus;
use App\Domain\Shared\Formatador;
use App\Domain\Shared\Relogio;
use App\Infrastructure\Persistencia\MapeadorDemanda;
use App\Models\Demanda;

/**
 * CSV de todas as demandas (seção 8.2): UTF-8 com BOM, ponto e vírgula, CRLF,
 * todos os campos entre aspas. Abre direto no Excel em português.
 */
final class ExportadorCsv
{
    private const BOM = "\u{FEFF}";

    private const COLUNAS = [
        'Entrada', 'Cliente', 'Descrição', 'Canal', 'Setor', 'Responsável', 'Acordo feito em', 'Prazo original',
        'Prazo atual', 'Adiamentos', 'Primeiro horário', 'Finalizada em', 'Situação', 'Observações',
        'Etapas', 'Ações realizadas', 'Como foi finalizada', 'Motivos de adiamento',
    ];

    public function __construct(
        private readonly MapeadorDemanda $mapeador,
        private readonly CalculadoraStatus $status,
        private readonly AnalisePrazos $prazos,
        private readonly Relogio $relogio,
    ) {}

    public static function nomeArquivo(string $hoje): string
    {
        return "demandas-$hoje.csv";
    }

    /** @param  resource  $saida */
    public function escrever($saida): void
    {
        $agora = $this->relogio->agora();
        fwrite($saida, self::BOM.$this->linha(self::COLUNAS));

        Demanda::query()->ativas()
            ->with(['cliente', 'responsavel', 'adiamentos', 'etapas', 'acoes'])
            ->orderBy('entrada')->orderBy('id')
            ->chunk(500, function ($lote) use ($saida, $agora) {
                foreach ($lote as $demanda) {
                    fwrite($saida, $this->linha($this->campos($this->mapeador->paraDados($demanda), $agora)));
                }
            });
    }

    /** @return list<string> */
    public function campos(DemandaDados $t, $agora): array
    {
        return [
            Formatador::csv($t->entrada),
            $t->cliente,
            $t->descricao,
            $t->canal,
            $t->setor,
            $t->responsavelNome ?? '',
            Formatador::csv($t->acordoEm),
            Formatador::csv($this->prazos->prazoOriginal($t)),
            Formatador::csv($t->prazo),
            (string) count($t->adiamentos),
            $t->primeiroHorario ? 'Sim' : '',
            Formatador::csv($t->fim),
            $this->status->calcular($t, $agora)->completo(),
            $t->observacoes ?? '',
            implode(' | ', array_map(fn ($e) => ($e->feita ? '[x] ' : '[ ] ').$e->texto, $t->etapas)),
            implode(' | ', array_map(fn ($a) => Formatador::csv($a->em).' – '.$a->texto, $t->acoes)),
            $t->conclusao ?? '',
            implode(' | ', array_map(fn ($a) => Formatador::csv($a->em).': '.$a->motivo,
                array_filter($t->adiamentos, fn ($a) => trim((string) $a->motivo) !== ''))),
        ];
    }

    /** @param  list<string>  $campos */
    private function linha(array $campos): string
    {
        return implode(';', array_map(fn ($c) => '"'.str_replace('"', '""', (string) $c).'"', $campos))."\r\n";
    }
}
