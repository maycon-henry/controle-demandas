<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Rules\Cnpj;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lista simples de clientes para corrigir nomes e CNPJ (T-08). */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('busca', ''));

        $clientes = Cliente::query()
            ->withCount(['demandas' => fn ($q) => $q->whereNull('excluido_em')])
            ->when($busca !== '', fn ($q) => $q->whereRaw('unaccent(lower(nome)) LIKE unaccent(?)', ['%'.mb_strtolower($busca).'%']))
            ->orderBy('nome')
            ->paginate(50)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'busca'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $request->merge(['cnpj' => preg_replace('/\D/', '', (string) $request->input('cnpj')) ?: null]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:200', function ($attr, $valor, $fail) use ($cliente) {
                $duplicado = Cliente::query()->whereRaw('lower(nome) = lower(?)', [trim($valor)])
                    ->whereKeyNot($cliente->id)->exists();
                if ($duplicado) {
                    $fail('Já existe um cliente com esse nome.');
                }
            }],
            'cnpj' => ['nullable', new Cnpj],
            'ativo' => ['boolean'],
        ]);

        $cliente->update([...$dados, 'ativo' => $request->boolean('ativo')]);

        return back()->with('aviso', 'Cliente atualizado');
    }
}
