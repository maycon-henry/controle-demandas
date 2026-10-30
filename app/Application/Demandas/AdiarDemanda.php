<?php

namespace App\Application\Demandas;

use App\Application\Excecoes\RegraViolada;
use App\Domain\Shared\Relogio;
use App\Models\Adiamento;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AdiarDemanda
{
    public function __construct(private readonly Historico $historico, private readonly Relogio $relogio) {}

    public function executar(string $id, CarbonImmutable $para, ?string $motivo, bool $primeiroHorario, Usuario $autor): Demanda
    {
        return DB::transaction(function () use ($id, $para, $motivo, $primeiroHorario, $autor) {
            /** @var Demanda $demanda */
            $demanda = Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);

            if ($demanda->fim !== null) {
                throw new RegraViolada('Esta tarefa já foi finalizada.');
            }

            $agora = $this->relogio->agora();
            $de = $demanda->prazo ?? $agora;
            $motivo = trim((string) $motivo) === '' ? null : trim($motivo);

            Adiamento::create([
                'demanda_id' => $demanda->id,
                'de' => $de,
                'para' => $para,
                'em' => $agora,
                'motivo' => $motivo,
                'usuario_id' => $autor->id,
            ]);

            $demanda->fill([
                'prazo' => $para,
                'primeiro_horario' => $primeiroHorario,
                'versao' => $demanda->versao + 1,
            ])->save();

            $this->historico->registrar($demanda, Evento::ADIADA, TextosHistorico::adiada($de, $para, $primeiroHorario, $motivo), $autor);

            return $demanda;
        });
    }
}
