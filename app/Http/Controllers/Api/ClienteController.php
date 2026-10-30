<?php

namespace App\Http\Controllers\Api;

use App\Application\Demandas\ResolvedorCliente;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Autocompletar do campo Cliente a partir de 2 letras, sem acentos e maiúsculas (T-08). */
class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        if (mb_strlen($termo) < 2) {
            return response()->json([]);
        }

        $padrao = '%'.addcslashes(mb_strtolower($termo), '%_\\').'%';

        return response()->json(Cliente::query()
            ->where('ativo', true)
            ->whereRaw('unaccent(lower(nome)) LIKE unaccent(?)', [$padrao])
            ->orderBy('nome')
            ->limit(15)
            ->pluck('nome'));
    }

    public function store(Request $request, ResolvedorCliente $resolvedor): JsonResponse
    {
        $dados = $request->validate(['nome' => ['required', 'string', 'max:200']]);

        return response()->json(['nome' => $resolvedor->resolver($dados['nome'])->nome], 201);
    }
}
