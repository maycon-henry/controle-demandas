@extends('layouts.interno')

@section('titulo', 'Usuários')

@section('conteudo')
    <div class="pagina-titulo">
        <h2>Usuários</h2>
        <a class="btn btn-primario" href="{{ route('usuarios.create') }}">+ Novo usuário</a>
    </div>
    @foreach ($errors->all() as $erro) <p class="erro" role="alert">{{ $erro }}</p> @endforeach
    <table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Setor</th><th>Situação</th><th>Ações</th></tr></thead>
        <tbody>
        @foreach ($usuarios as $u)
            <tr>
                <td><strong>{{ $u->nome }}</strong></td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->ehGestor() ? 'Gestor' : 'Colaborador' }}</td>
                <td>{{ $u->setor ?? '—' }}</td>
                <td>@if ($u->ativo) <span class="selo">ativo</span> @else <span class="selo selo-inativo">inativo</span> @endif</td>
                <td>
                    <div class="acoes-tabela">
                        <a class="btn btn-pequeno" href="{{ route('usuarios.edit', $u) }}">Editar</a>
                        <form method="post" action="{{ route('usuarios.situacao', $u) }}">
                            @csrf
                            <button type="submit" class="btn btn-pequeno {{ $u->ativo ? 'btn-perigo' : '' }}">{{ $u->ativo ? 'Desativar' : 'Reativar' }}</button>
                        </form>
                        @if ($u->ativo)
                            <form method="post" action="{{ route('usuarios.convite', $u) }}">
                                @csrf
                                <button type="submit" class="btn btn-pequeno">Reenviar convite</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
