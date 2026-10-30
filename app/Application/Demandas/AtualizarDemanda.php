<?php

namespace App\Application\Demandas;

use App\Application\Excecoes\ConflitoDeVersao;
use App\Domain\Shared\Relogio;
use App\Models\Adiamento;
use App\Models\Demanda;
use App\Models\Evento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Edição pelo formulário, com controle de versão otimista (seção 10). */
final class AtualizarDemanda
{
    public function __construct(
        private readonly ResolvedorCliente $clientes,
        private readonly ReconciliadorEtapas $etapas,
        private readonly Historico $historico,
        private readonly Relogio $relogio,
    ) {}

    public function executar(string $id, int $versaoEsperada, DadosFormulario $dados, Usuario $autor): Demanda
    {
        return DB::transaction(function () use ($id, $versaoEsperada, $dados, $autor) {
            /** @var Demanda $demanda */
            $demanda = Demanda::query()->ativas()->lockForUpdate()->findOrFail($id);

            if ($demanda->versao !== $versaoEsperada) {
                throw new ConflitoDeVersao;
            }

            $alteracoes = $this->descreverAlteracoes($demanda, $dados);
            $this->registrarAdiamentoSePrazoAumentou($demanda, $dados, $autor);

            $demanda->fill([
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
                'conclusao' => $dados->conclusao ?? $demanda->conclusao,
                'observacoes' => $dados->observacoes,
                'versao' => $demanda->versao + 1,
            ])->save();

            $this->etapas->reconciliar($demanda, $dados->etapas, $autor);
            $this->historico->registrar($demanda, Evento::EDITADA, TextosHistorico::editada($alteracoes), $autor);

            return $demanda;
        });
    }

    /** Aumentar o prazo de uma demanda que já tinha prazo conta como adiamento sem motivo (seção 6.3). */
    private function registrarAdiamentoSePrazoAumentou(Demanda $demanda, DadosFormulario $dados, Usuario $autor): void
    {
        if ($demanda->prazo === null || $dados->prazo === null || $dados->prazo <= $demanda->prazo) {
            return;
        }

        Adiamento::create([
            'demanda_id' => $demanda->id,
            'de' => $demanda->prazo,
            'para' => $dados->prazo,
            'em' => $this->relogio->agora(),
            'motivo' => null,
            'usuario_id' => $autor->id,
        ]);
    }

    /** @return list<string> */
    private function descreverAlteracoes(Demanda $demanda, DadosFormulario $dados): array
    {
        $itens = [];

        if (! $this->mesmoMomento($demanda->prazo, $dados->prazo)) {
            $itens[] = TextosHistorico::itemPrazo($demanda->prazo, $dados->prazo);
        }

        if ($demanda->responsavel_id !== $dados->responsavelId) {
            $itens[] = TextosHistorico::itemResponsavel(
                $demanda->responsavel?->nome,
                $dados->responsavelId ? Usuario::find($dados->responsavelId)?->nome : null,
            );
        }

        if ($dados->fim !== null && ! $this->mesmoMomento($demanda->fim, $dados->fim)) {
            $itens[] = TextosHistorico::itemFinalizada($dados->fim);
        } elseif ($dados->fim === null && $demanda->fim !== null) {
            $itens[] = TextosHistorico::itemReaberta();
        }

        return $itens;
    }

    private function mesmoMomento($a, $b): bool
    {
        return $a?->getTimestamp() === $b?->getTimestamp();
    }
}
