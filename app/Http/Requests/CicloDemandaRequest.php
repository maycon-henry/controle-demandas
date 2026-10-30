<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Painéis Adiar, Finalizar, Nova etapa e Registrar ação (seções 6.3 a 6.5).
 * As regras dependem da ação, identificada pelo nome da rota.
 */
class CicloDemandaRequest extends FormRequest
{
    private const DATA = 'date_format:'.SalvarDemandaRequest::FORMATO_DATA;

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'adiar' => [
                'para' => ['required', self::DATA],
                'motivo' => ['nullable', 'string', 'max:1000'],
                'primeiro_horario' => ['boolean'],
            ],
            'finalizar' => [
                'conclusao' => ['required', 'string', 'max:5000'],
                'fim' => ['required', self::DATA],
                'marcar_etapas' => ['boolean'],
            ],
            'adicionarEtapa' => ['texto' => ['required', 'string', 'max:500']],
            'marcarEtapa' => ['feita' => ['required', 'boolean']],
            'registrarAcao' => [
                'em' => ['required', self::DATA],
                'texto' => ['required', 'string', 'max:5000'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'para.required' => 'Informe o novo prazo.',
            'conclusao.required' => 'Descreva como a tarefa foi finalizada.',
            'fim.required' => 'Informe quando foi finalizada.',
            'texto.required' => $this->route()->getActionMethod() === 'registrarAcao'
                ? 'Descreva a ação realizada.'
                : 'Descreva a etapa.',
            'em.required' => 'Informe quando a ação foi feita.',
            '*.date_format' => 'Data e hora inválidas.',
        ];
    }
}
