<?php

namespace App\Application\Relatorio;

use App\Domain\Relatorio\GeradorRelatorioDia;
use App\Domain\Relatorio\RelatorioDia;
use App\Domain\Shared\Relogio;
use App\Infrastructure\Persistencia\ConsultaDemandas;
use App\Infrastructure\Persistencia\MapeadorDemanda;
use App\Models\Demanda;
use App\Models\Usuario;
use Illuminate\Support\Collection;

/** Une consulta ao banco e regras de domínio para produzir o relatório de um dia. */
final class ServicoRelatorioDia
{
    public function __construct(
        private readonly ConsultaDemandas $consulta,
        private readonly MapeadorDemanda $mapeador,
        private readonly GeradorRelatorioDia $gerador,
        private readonly Relogio $relogio,
    ) {}

    /** @return array{0: RelatorioDia, 1: Collection<string, Demanda>, 2: ?string} relatório, modelos por id e nome do filtro */
    public function gerar(string $dia, ?string $responsavelId): array
    {
        $modelos = $this->consulta->candidatasRelatorio($dia)->keyBy('id');
        $dados = $modelos->map(fn (Demanda $m) => $this->mapeador->paraDados($m))->values();

        $relatorio = $this->gerador->gerar($dados, $dia, $responsavelId, $this->relogio->agora());
        $nomeFiltro = $responsavelId ? Usuario::find($responsavelId)?->nome : null;

        return [$relatorio, $modelos, $nomeFiltro];
    }
}
