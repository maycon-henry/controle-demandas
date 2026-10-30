@extends('layouts.acesso')

@section('titulo', 'Entrar')

@section('cartao')
    <h2>Entrar</h2>
    @if (session('aviso'))
        <div class="aviso-ok">{{ session('aviso') }}</div>
    @endif
    <form method="post" action="/login" novalidate>
        @csrf
        <div class="campo">
            <label class="rotulo" for="email">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        </div>
        <div class="campo">
            <label class="rotulo" for="senha">Senha</label>
            <div class="campo-senha">
                <input type="password" id="senha" name="senha" autocomplete="current-password" required>
                <button type="button" class="link" data-alternar-senha="senha" aria-controls="senha">Mostrar</button>
            </div>
        </div>
        <label class="caixa"><input type="checkbox" name="manter_conectado" value="1" @checked(old('manter_conectado', true))> Manter conectado</label>
        @error('email') <p class="erro" role="alert">{{ $message }}</p> @enderror
        @error('senha') <p class="erro" role="alert">{{ $message }}</p> @enderror
        <button type="submit" class="btn btn-primario">Entrar</button>
    </form>
    <div class="rodape-login"><a href="{{ route('senha.esqueci') }}">Esqueci minha senha</a></div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-alternar-senha]').forEach((b) => b.addEventListener('click', () => {
        const campo = document.getElementById(b.dataset.alternarSenha);
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        b.textContent = mostrar ? 'Ocultar' : 'Mostrar';
    }));
</script>
@endpush
