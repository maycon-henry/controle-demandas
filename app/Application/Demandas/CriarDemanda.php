<?php

namespace App\Application\Demandas;

use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

final class CriarDemanda
{
    public function __construct(
        private readonly ResolvedorCliente $clientes,
        private readonly ReconciliadorEtapas $etapas,
        private readonly Historico $historico,
    ) {}

    public function executar(DadosFormulario $dados, Usuario $autor): Demanda
    {
        return DB::transaction(function () use ($dados, $autor) {
            $demanda = Demanda::create([
                'entrada' => $dados->entrada,
                'cliente_id' => $this->clientes->resolver($dados->cliente)->id,
                'descricao' => $dados->descricao,
                'canal' => $dados->canal,
                'setor' => $dados->setor,
                'responsavel_id' => $dados->responsavelId,
                'acordo_em' => $dados->acordoEm,
                'prazo' => $dados->prazo,
                'primeiro_horario' => $dados->primeiroHorario,
                'fim' => $dados->fim,
                'conclusao' => $dados->conclusao,
                'observacoes' => $dados->observacoes,
                'criado_por' => $autor->id,
            ]);

            $this->etapas->reconciliar($demanda, $dados->etapas, $autor);

            $responsavel = $dados->responsavelId ? Usuario::find($dados->responsavelId)?->nome : null;
            $this->historico->registrar($demanda, Evento::CRIADA, TextosHistorico::criada($dados->canal, $responsavel), $autor);

            if ($dados->fim !== null) {
                $this->historico->registrar($demanda, Evento::FINALIZADA, TextosHistorico::finalizadaNaCriacao(), $autor, $dados->fim);
            }

            return $demanda;
        });
    }
}
