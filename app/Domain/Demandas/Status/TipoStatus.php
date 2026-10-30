<?php

namespace App\Domain\Demandas\Status;

/** Os sete status possíveis, na ordem das regras da seção 6.1. */
enum TipoStatus: string
{
    case ConcluidaNoPrazo = 'concluida_no_prazo';
    case ConcluidaComAtraso = 'concluida_com_atraso';
    case Atrasada = 'atrasada';
    case Adiada = 'adiada';
    case VenceHoje = 'vence_hoje';
    case NoPrazo = 'no_prazo';
    case SemPrazo = 'sem_prazo';

    public function grupo(): GrupoStatus
    {
        return match ($this) {
            self::ConcluidaNoPrazo, self::ConcluidaComAtraso => GrupoStatus::Concluida,
            self::Adiada => GrupoStatus::Adiada,
            default => GrupoStatus::Pendente,
        };
    }

    /** Cor do selo. */
    public function cor(): string
    {
        return match ($this) {
            self::ConcluidaNoPrazo => 'verde',
            self::ConcluidaComAtraso, self::Adiada => 'ambar',
            self::Atrasada => 'vermelho',
            default => 'azul',
        };
    }
}
