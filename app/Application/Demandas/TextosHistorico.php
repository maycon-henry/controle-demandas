<?php

namespace App\Application\Demandas;

use App\Domain\Shared\Formatador;
use Carbon\CarbonInterface;

/** Frases do histórico automático (seção 6.6), num só lugar. */
final class TextosHistorico
{
    public static function criada(string $canal, ?string $responsavel): string
    {
        return sprintf('Tarefa lançada (%s) para %s.', $canal, $responsavel ?? 'sem responsável');
    }

    public static function finalizadaNaCriacao(): string
    {
        return 'Finalizada.';
    }

    public static function adiada(CarbonInterface $de, CarbonInterface $para, bool $primeiroHorario, ?string $motivo): string
    {
        $texto = sprintf('Adiada de %s para %s', Formatador::dataHora($de), Formatador::dataHora($para));

        if ($primeiroHorario) {
            $texto .= ' (primeiro horário)';
        }

        $motivo = rtrim(trim((string) $motivo), '.');
        if ($motivo !== '') {
            $texto .= '. Motivo: '.$motivo;
        }

        return $texto.'.';
    }

    /** @param  list<string>  $itens */
    public static function editada(array $itens): string
    {
        return $itens === [] ? 'Dados da tarefa editados.' : 'Editada: '.implode('; ', $itens).'.';
    }

    public static function itemPrazo(?CarbonInterface $de, ?CarbonInterface $para): string
    {
        return sprintf('prazo de %s para %s', self::dataOuSem($de), self::dataOuSem($para));
    }

    public static function itemResponsavel(?string $de, ?string $para): string
    {
        return sprintf('responsável de %s para %s', $de ?? '(sem responsável)', $para ?? '(sem responsável)');
    }

    public static function itemFinalizada(CarbonInterface $fim): string
    {
        return 'finalizada em '.Formatador::dataHora($fim);
    }

    public static function itemReaberta(): string
    {
        return 'reaberta';
    }

    public static function etapa(bool $feita, string $texto): string
    {
        return ($feita ? 'Etapa concluída: ' : 'Etapa desmarcada: ').$texto;
    }

    public static function finalizada(string $conclusao): string
    {
        return 'Finalizada: '.trim($conclusao);
    }

    public static function reaberta(): string
    {
        return 'Reaberta.';
    }

    public static function excluida(): string
    {
        return 'Excluída.';
    }

    private static function dataOuSem(?CarbonInterface $m): string
    {
        return $m ? Formatador::dataHora($m) : 'sem prazo';
    }
}
