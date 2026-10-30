@extends('layouts.interno')

@section('titulo', $usuario->exists ? 'Editar usuário' : 'Novo usuário')

@section('conteudo')
    <div class="pagina-titulo">
        <h2>{{ $usuario->exists ? 'Editar usuário' : 'Novo usuário' }}</h2>
        <a class="btn" href="{{ route('usuarios.index') }}">Voltar</a>
    </div>
    <form class="cartao-form" method="post" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" novalidate>
        @csrf
        @if ($usuario->exists) @method('put') @endif
        <div class="campo">
            <label class="rotulo" for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="{{ old('nome', $usuario->nome) }}" required autofocus>
        </div>
        <div class="campo">
            <label class="rotulo" for="email">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
        </div>
        <div class="campo">
            <label class="rotulo" for="perfil">Perfil</label>
            <select id="perfil" name="perfil">
                <option value="colaborador" @selected(old('perfil', $usuario->perfil) === 'colaborador')>Colaborador</option>
                <option value="gestor" @selected(old('perfil', $usuario->perfil) === 'gestor')>Gestor</option>
            </select>
        </div>
        <div class="campo">
            <label class="rotulo" for="setor">Setor</label>
            <select id="setor" name="setor">
                <option value="">—</option>
                @foreach ($setores as $s)
                    <option @selected(old('setor', $usuario->setor) === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo">
            <label class="rotulo" for="senha_provisoria">Senha provisória (opcional)</label>
            <input type="text" id="senha_provisoria" name="senha_provisoria" autocomplete="off">
            <div class="dica">
                @if ($usuario->exists)
                    Preencha só para redefinir a senha. A pessoa terá de trocá-la no próximo acesso.
                @else
                    Em branco: enviamos por e-mail um convite (válido por 7 dias) para a pessoa criar a senha.
                    Preenchida: a pessoa entra com ela e é obrigada a trocá-la no primeiro acesso.
                @endif
            </div>
        </div>
        @foreach ($errors->all() as $erro) <p class="erro" role="alert">{{ $erro }}</p> @endforeach
        <div class="botoes-form">
            <a class="btn" href="{{ route('usuarios.index') }}">Cancelar</a>
            <button type="submit" class="btn btn-primario">Salvar usuário</button>
        </div>
    </form>
@endsection
