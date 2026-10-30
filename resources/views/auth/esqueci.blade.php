@extends('layouts.acesso')

@section('titulo', 'Esqueci minha senha')

@section('cartao')
    <h2>Esqueci minha senha</h2>
    @if (session('aviso'))
        <div class="aviso-ok">{{ session('aviso') }}</div>
    @endif
    <p class="dica" style="margin-bottom:14px">Informe seu e-mail. Enviaremos um link para criar uma nova senha.</p>
    <form method="post" action="/esqueci" novalidate>
        @csrf
        <div class="campo">
            <label class="rotulo" for="email">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        @error('email') <p class="erro" role="alert">{{ $message }}</p> @enderror
        <button type="submit" class="btn btn-primario">Enviar link</button>
    </form>
    <div class="rodape-login"><a href="{{ route('login') }}">Voltar para o login</a></div>
@endsection
