@extends('layouts.interno')

@section('titulo', ['relatorio' => 'Relatório do dia', 'tarefas' => 'Todas as tarefas', 'equipe' => 'Equipe'][$aba])

@section('conteudo')
    <div class="barra-fixa">
        <button type="button" class="btn btn-primario" data-acao="nova-tarefa">+ Nova tarefa</button>
        <a class="btn" href="{{ route('demandas.csv') }}" download>Exportar CSV</a>
    </div>

    <section id="formulario" aria-live="polite"></section>

    <section id="vista" data-aba-inicial="{{ $aba }}">
        <p class="carregando">Carregando…</p>
    </section>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@endpush
