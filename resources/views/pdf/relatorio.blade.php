<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de demandas do dia</title>
<style>
    @page { margin: 14mm 14mm 16mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 7.5pt; color: #1b2433; margin: 0; }
    .faixa { background: #13284D; color: #fff; padding: 5mm 6mm; margin-bottom: 5mm; }
    .faixa .escritorio { font-size: 6.5pt; letter-spacing: .4pt; color: #c9d3e6; }
    .faixa h1 { font-size: 14pt; margin: 1.5mm 0 1mm; }
    .faixa .sub { font-size: 8pt; color: #dbe3f1; }
    table.cartoes { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm 4mm; }
    table.cartoes td { border: 0.6pt solid #d8dee9; background: #f6f8fb; padding: 2.5mm 3mm; width: 16.6%; }
    .cartoes .rot { font-size: 6pt; color: #5b6575; text-transform: uppercase; letter-spacing: .3pt; }
    .cartoes .num { font-size: 13pt; font-weight: bold; margin-top: 1.5mm; }
    .secao { page-break-inside: auto; }
    h2 { font-size: 10pt; margin: 5mm 0 2mm; color: #13284D; page-break-after: avoid; }
    h2.verde { color: #1f7a45; } h2.vermelho { color: #b42318; } h2.azul { color: #1f4f9c; } h2.ambar { color: #9a6200; }
    .vazio { color: #5b6575; font-style: italic; margin-bottom: 2mm; }
    table.dados { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.dados thead { display: table-header-group; }
    table.dados th { background: #13284D; color: #fff; text-align: left; font-size: 6.8pt; padding: 1.6mm 1.8mm; }
    table.dados td { border-bottom: 0.5pt solid #d8dee9; padding: 1.6mm 1.8mm; vertical-align: top; word-wrap: break-word; }
    table.dados tr:nth-child(even) td { background: #f6f8fb; }
    .neg { font-weight: bold; }
    .num-col { text-align: right; }
</style>
</head>
<body>
    <div class="faixa">
        <div class="escritorio">BITARÃES ASSESSORIA E CONSULTORIA CONTÁBIL</div>
        <h1>Relatório de demandas do dia</h1>
        <div class="sub">{{ $subtitulo }}</div>
    </div>

    <table class="cartoes"><tr>
        @foreach ($cartoes as $rotulo => $valor)
            <td><div class="rot">{{ $rotulo }}</div><div class="num">{{ $valor }}</div></td>
        @endforeach
    </tr></table>

    {{-- Título, cabeçalho e primeira linha ficam juntos (a seção não começa órfã no pé da página);
         as demais linhas seguem numa continuação da mesma tabela. --}}
    @foreach ($secoes as $secao)
        <div style="page-break-inside: avoid">
            <h2 class="{{ $secao['cor'] }}">{{ $secao['titulo'] }} ({{ count($secao['linhas']) }})</h2>
            @if ($secao['linhas'] === [])
                <div class="vazio">{{ $secao['vazio'] }}</div>
            @else
                @include('pdf.tabela-demandas', ['linhas' => array_slice($secao['linhas'], 0, 1)])
            @endif
        </div>
        @if (count($secao['linhas']) > 1)
            @include('pdf.tabela-demandas', ['linhas' => array_slice($secao['linhas'], 1), 'cabecalho' => false])
        @endif
    @endforeach

    @if ($resumo !== null)
        <h2>Resumo por colaborador</h2>
        @if ($resumo === [])
            <div class="vazio">Sem movimento neste dia.</div>
        @else
            <table class="dados">
                <thead><tr>
                    <th>Colaborador</th><th class="num-col">Entraram</th><th class="num-col">Finalizadas</th>
                    <th class="num-col">Pendentes</th><th class="num-col">Adiadas no dia</th><th class="num-col">Previstas p/ amanhã</th>
                </tr></thead>
                <tbody>
                @foreach ($resumo as $r)
                    <tr>
                        <td class="neg">{{ $r['nome'] }}</td><td class="num-col">{{ $r['entraram'] }}</td>
                        <td class="num-col">{{ $r['finalizadas'] }}</td><td class="num-col">{{ $r['pendentes'] }}</td>
                        <td class="num-col">{{ $r['adiadas'] }}</td><td class="num-col">{{ $r['amanha'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endif
</body>
</html>
