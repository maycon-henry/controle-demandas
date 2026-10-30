@extends('layouts.acesso')

@section('titulo', $titulo)

@section('cartao')
    <h2>{{ $titulo }}</h2>
    <form method="post" action="{{ $acao }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="campo">
            <label class="rotulo" for="email">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
        </div>
        <div class="campo">
            <label class="rotulo" for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha" autocomplete="new-password" required>
            <div class="dica">Mínimo de 8 caracteres, com letras e números.</div>
        </div>
        <div class="campo">
            <label class="rotulo" for="senha_confirmation">Repita a nova senha</label>
            <input type="password" id="senha_confirmation" name="senha_confirmation" autocomplete="new-password" required>
        </div>
        @foreach ($errors->all() as $erro) <p class="erro" role="alert">{{ $erro }}</p> @endforeach
        <button type="submit" class="btn btn-primario">Salvar senha</button>
    </form>
@endsection
