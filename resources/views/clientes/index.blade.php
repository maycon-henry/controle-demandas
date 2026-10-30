@extends('layouts.interno')

@section('titulo', 'Clientes')

@section('conteudo')
    <div class="pagina-titulo">
        <h2>Clientes</h2>
        <form method="get" action="{{ route('clientes.index') }}" style="display:flex;gap:8px">
            <input type="search" name="busca" value="{{ $busca }}" placeholder="Buscar cliente" aria-label="Buscar cliente">
            <button type="submit" class="btn">Buscar</button>
        </form>
    </div>
    <p class="nota" style="margin-top:0">Clientes novos são criados automaticamente ao salvar uma demanda. Use esta tela para corrigir nomes e informar o CNPJ.</p>
    @foreach ($errors->all() as $erro) <p class="erro" role="alert">{{ $erro }}</p> @endforeach
    @if ($clientes->isEmpty())
        <div class="vazio">Nenhum cliente encontrado.</div>
    @else
        <table class="tabela">
            <thead><tr><th>Nome</th><th>CNPJ (só números)</th><th>Ativo</th><th>Demandas</th><th></th></tr></thead>
            <tbody>
            @foreach ($clientes as $c)
                <tr>
                    <td><input form="cliente-{{ $c->id }}" type="text" name="nome" value="{{ $c->nome }}" aria-label="Nome do cliente" required></td>
                    <td><input form="cliente-{{ $c->id }}" type="text" name="cnpj" value="{{ $c->cnpj }}" inputmode="numeric" maxlength="18" aria-label="CNPJ"></td>
                    <td><input form="cliente-{{ $c->id }}" type="checkbox" name="ativo" value="1" @checked($c->ativo) aria-label="Ativo"></td>
                    <td class="mono">{{ $c->demandas_count }}</td>
                    <td>
                        <form id="cliente-{{ $c->id }}" method="post" action="{{ route('clientes.update', $c) }}">
                            @csrf @method('put')
                            <button type="submit" class="btn btn-pequeno">Salvar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginacao">
            @if ($clientes->previousPageUrl()) <a class="btn btn-pequeno" href="{{ $clientes->previousPageUrl() }}">Anterior</a> @endif
            @if ($clientes->nextPageUrl()) <a class="btn btn-pequeno" href="{{ $clientes->nextPageUrl() }}">Próxima</a> @endif
        </div>
    @endif
@endsection
