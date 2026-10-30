@extends('layouts.base')

@section('classe-body', 'pagina-login')

@section('corpo')
<div class="faixa-login">
    <div class="escritorio">{{ config('escritorio.nome') }}</div>
    <h1>Controle de Demandas</h1>
</div>
<main class="cartao-login">
    @yield('cartao')
</main>
<p class="produto">Prazo Certo</p>
@endsection
