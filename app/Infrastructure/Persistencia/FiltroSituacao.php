<?php

namespace App\Infrastructure\Persistencia;

/** Opções do filtro de situação da aba "Todas as tarefas" (seção 7.5). */
enum FiltroSituacao: string
{
    case Todas = '';
    case Concluidas = 'concluida';
    case Pendentes = 'pendente';
    case Adiadas = 'adiada';
    case Atrasadas = 'atrasada';
    case VencemHoje = 'vence_hoje';

    public function rotulo(): string
    {
        return match ($this) {
            self::Todas => 'Todas as situações',
            self::Concluidas => 'Concluídas',
            self::Pendentes => 'Pendentes',
            self::Adiadas => 'Adiadas',
            self::Atrasadas => 'Pendentes atrasadas',
            self::VencemHoje => 'Vencem hoje',
        };
    }
}
