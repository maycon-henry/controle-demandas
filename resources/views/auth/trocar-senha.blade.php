@extends('layouts.acesso')

@section('titulo', 'Trocar senha')

@section('cartao')
    <h2>Crie sua senha</h2>
    <p class="dica" style="margin-bottom:14px">Você entrou com uma senha provisória. Escolha uma senha nova para continuar.</p>
    <form method="post" action="/trocar-senha" novalidate>
        @csrf
        <div class="campo">
            <label class="rotulo" for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha" autocomplete="new-password" required autofocus>
            <div class="dica">Mínimo de 8 caracteres, com letras e números.</div>
        </div>
        <div class="campo">
            <label class="rotulo" for="senha_confirmation">Repita a nova senha</label>
            <input type="password" id="senha_confirmation" name="senha_confirmation" autocomplete="new-password" required>
        </div>
        @foreach ($errors->all() as $erro) <p class="erro" role="alert">{{ $erro }}</p> @endforeach
        <button type="submit" class="btn btn-primario">Salvar senha</button>
    </form>
    <form method="post" action="{{ route('logout') }}" class="rodape-login">
        @csrf
        <button type="submit" class="link">Sair</button>
    </form>
@endsection
