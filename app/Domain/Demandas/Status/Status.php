<?php

namespace App\Domain\Demandas\Status;

final class Status
{
    public function __construct(
        public readonly TipoStatus $tipo,
        public readonly string $titulo,
        public readonly string $complemento,
    ) {}

    public function grupo(): GrupoStatus
    {
        return $this->tipo->grupo();
    }

    /** "Concluída · no prazo" */
    public function exibicao(): string
    {
        return $this->titulo.' · '.$this->complemento;
    }

    /** "Concluída (no prazo)", usado em CSV e PDF. */
    public function completo(): string
    {
        return $this->titulo.' ('.$this->complemento.')';
    }

    /** @return array{tipo: string, grupo: string, cor: string, titulo: string, complemento: string} */
    public function paraArray(): array
    {
        return [
            'tipo' => $this->tipo->value,
            'grupo' => $this->grupo()->value,
            'cor' => $this->tipo->cor(),
            'titulo' => $this->titulo,
            'complemento' => $this->complemento,
        ];
    }
}
