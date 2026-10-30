<?php

namespace App\Http\Requests;

use App\Domain\Demandas\ListasFixas;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('usuario')?->id;

        return [
            'nome' => ['required', 'string', 'max:120',
                Rule::unique('usuarios', 'nome')->where('ativo', true)->ignore($id)],
            'email' => ['required', 'email', 'max:200', Rule::unique('usuarios', 'email')->ignore($id)],
            'perfil' => ['required', Rule::in([Usuario::PERFIL_GESTOR, Usuario::PERFIL_COLABORADOR])],
            'setor' => ['nullable', Rule::in(ListasFixas::SETORES)],
            'senha_provisoria' => ['nullable', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome.',
            'nome.unique' => 'Já existe um usuário ativo com esse nome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha_provisoria' => 'A senha deve ter pelo menos 8 caracteres, com letras e números.',
        ];
    }

    /** @return array{nome: string, email: string, perfil: string, setor: ?string} */
    public function dados(): array
    {
        return [
            'nome' => trim($this->validated('nome')),
            'email' => mb_strtolower(trim($this->validated('email'))),
            'perfil' => $this->validated('perfil'),
            'setor' => $this->validated('setor') ?: null,
        ];
    }

    public function senhaProvisoria(): ?string
    {
        return $this->validated('senha_provisoria') ?: null;
    }
}
