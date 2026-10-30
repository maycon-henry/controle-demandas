<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Formatador;
use App\Domain\Shared\Fuso;
use App\Domain\Shared\Relogio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AplicacaoController extends Controller
{
    private const ABAS = ['relatorio', 'tarefas', 'equipe'];

    /** Casca das três abas; o conteúdo é montado pela interface a partir da API. */
    public function mostrar(Request $request, Relogio $relogio): View
    {
        $aba = $request->route()->getName();

        return view('aplicacao', [
            'aba' => in_array($aba, self::ABAS, true) ? $aba : 'relatorio',
            'hojeExtenso' => Formatador::extenso(Fuso::dia($relogio->agora())),
        ]);
    }

    /** Manual rápido (T-26). */
    public function ajuda(Relogio $relogio): View
    {
        return view('ajuda', ['hojeExtenso' => Formatador::extenso(Fuso::dia($relogio->agora()))]);
    }
}
