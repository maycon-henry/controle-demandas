<?php

namespace App\Http\Requests;

use App\Application\Demandas\DadosFormulario;
use App\Application\Demandas\FinalizarDemanda;
use App\Domain\Demandas\ListasFixas;
use App\Domain\Shared\Fuso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Formulário "Nova tarefa / Editar tarefa" (seções 7.4 e 9). */
class SalvarDemandaRequest extends FormRequest
{
    public const FORMATO_DATA = 'Y-m-d\TH:i';

    public function rules(): array
    {
        $data = ['nullable', 'date_format:'.self::FORMATO_DATA];

        return [
            'entrada' => $data,
            'cliente' => ['nullable', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'canal' => ['nullable', 'string', 'max:100'],
            'canal_outro' => ['nullable', 'string', 'max:100'],
            'setor' => ['nullable', Rule::in(ListasFixas::SETORES)],
            'responsavel_id' => ['nullable', 'uuid', Rule::exists('usuarios', 'id')],
            'acordo_em' => $data,
            'prazo' => $data,
            'primeiro_horario' => ['boolean'],
            'fim' => $data,
            'observacoes' => ['nullable', 'string', 'max:500'],
            'etapas' => ['nullable', 'string', 'max:10000'],
            'conclusao' => ['nullable', 'string', 'max:5000'],
            'versao' => [$this->isMethod('patch') ? 'required' : 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.date_format' => 'Data e hora inválidas.',
            'descricao.max' => 'A descrição pode ter no máximo 5.000 caracteres.',
            'responsavel_id.exists' => 'Responsável não encontrado.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $faltando = array_keys(array_filter([
                'o dia e hora de entrada' => blank($this->input('entrada')),
                'o cliente' => blank($this->input('cliente')),
                'a descrição' => blank($this->input('descricao')),
            ]));

            if ($faltando !== []) {
                $v->errors()->add('formulario', 'Preencha '.self::juntar($faltando).'.');

                return;
            }

            if (filled($this->input('fim'))) {
                if (blank($this->input('conclusao'))) {
                    $v->errors()->add('conclusao', 'Descreva como a tarefa foi finalizada.');
                } elseif (Fuso::parse($this->input('fim')) < Fuso::parse($this->input('entrada'))) {
                    $v->errors()->add('fim', FinalizarDemanda::MSG_FIM_ANTES_DA_ENTRADA);
                }
            }
        }];
    }

    public function dados(): DadosFormulario
    {
        return DadosFormulario::deArray($this->validated());
    }

    /** "a, b e c" */
    private static function juntar(array $itens): string
    {
        $ultimo = array_pop($itens);

        return $itens === [] ? $ultimo : implode(', ', $itens).' e '.$ultimo;
    }
}
