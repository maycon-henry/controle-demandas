{{-- Colunas da seção 8.1, em proporção às larguras em mm (30, 92, 24, 25, 54, 44). --}}
<table class="dados">
    @if ($cabecalho ?? true)
    <thead><tr>
        <th style="width:11.2%">Cliente</th>
        <th style="width:34.2%">Demanda, etapas e ações realizadas</th>
        <th style="width:8.9%">Responsável</th>
        <th style="width:9.3%">Entrada</th>
        <th style="width:20.1%">Acordos firmados com o cliente</th>
        <th style="width:16.3%">Finalização</th>
    </tr></thead>
    @endif
    <tbody>
    @foreach ($linhas as $l)
        <tr>
            <td class="neg" style="width:11.2%">{{ $l['cliente'] }}</td>
            <td style="width:34.2%">@foreach ($l['demanda'] as $t){!! str_replace('   ', '&nbsp;&nbsp;&nbsp;', e($t)) !!}<br>@endforeach</td>
            <td style="width:8.9%">@foreach ($l['responsavel'] as $t){{ $t }}<br>@endforeach</td>
            <td style="width:9.3%">@foreach ($l['entrada'] as $t){{ $t }}<br>@endforeach</td>
            <td style="width:20.1%">@foreach ($l['acordos'] as $t){{ $t }}<br>@endforeach</td>
            <td style="width:16.3%">@foreach ($l['finalizacao'] as $t){{ $t }}<br>@endforeach</td>
        </tr>
    @endforeach
    </tbody>
</table>
