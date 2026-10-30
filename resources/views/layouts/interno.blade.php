@extends('layouts.base')

@section('corpo')
<header class="cabecalho">
    <div class="container">
        <div class="cabecalho-topo">
            <div>
                <div class="escritorio">{{ config('escritorio.nome') }}</div>
                <h1 class="titulo-app"><a href="/relatorio">Controle de Demandas</a></h1>
            </div>
            <div class="cabecalho-direita">
                <div class="hoje-extenso">{{ $hojeExtenso ?? '' }}</div>
                <nav class="menu-usuario" aria-label="Conta">
                    <span class="nome-usuario">{{ auth()->user()->nome }}</span>
                    @can('gerir-usuarios')
                        <a href="{{ route('usuarios.index') }}">Usuários</a>
                    @endcan
                    <a href="{{ route('clientes.index') }}">Clientes</a>
                    <a href="{{ route('ajuda') }}">Ajuda</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="link-sair">Sair</button>
                    </form>
                </nav>
            </div>
        </div>
        <nav class="abas" aria-label="Seções">
            <a href="/relatorio" data-aba="relatorio" @class(['aba', 'ativa' => ($aba ?? '') === 'relatorio'])>Relatório do dia</a>
            <a href="/tarefas" data-aba="tarefas" @class(['aba', 'ativa' => ($aba ?? '') === 'tarefas'])>Todas as tarefas</a>
            <a href="/equipe" data-aba="equipe" @class(['aba', 'ativa' => ($aba ?? '') === 'equipe'])>Equipe</a>
        </nav>
    </div>
</header>
<main class="container conteudo">
    @yield('conteudo')
</main>
@endsection
