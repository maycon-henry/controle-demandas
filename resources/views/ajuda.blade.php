@extends('layouts.interno')

@section('titulo', 'Ajuda')

@section('conteudo')
    <div class="pagina-titulo"><h2>Manual rápido</h2></div>
    <article class="texto-ajuda">
        <h3>1. Lançar uma demanda</h3>
        <p>Toque em <strong>+ Nova tarefa</strong>. Informe quando a demanda chegou, o cliente, o que foi pedido e por qual canal.
            Se já combinou o prazo com o cliente, preencha <em>Acordo feito em</em> e <em>Prazo acordado</em>.
            Marque <em>Fazer no primeiro horário</em> quando ela precisa ser a primeira coisa do dia do prazo.
            Se a demanda tem passos, escreva um por linha em <em>Etapas</em>.</p>

        <h3>2. Registrar o que foi feito</h3>
        <p>Abra <strong>Detalhes</strong> na linha da tarefa. Marque as etapas concluídas e, em <em>Ações realizadas</em>,
            escreva o que foi feito (ex.: “Cobrado o extrato do Sicoob por WhatsApp”) e toque em <strong>Registrar</strong>.
            Você pode ajustar a data e a hora se fez a ação mais cedo.</p>

        <h3>3. Adiar com motivo</h3>
        <p>Se o prazo não vai ser cumprido, toque em <strong>Adiar</strong>, informe o novo prazo e o motivo.
            Todo adiamento fica no histórico e aparece no relatório e na aba Equipe.</p>

        <h3>4. Finalizar com descrição</h3>
        <p>Toque em <strong>Finalizar agora</strong> e descreva como foi finalizada: o que foi entregue, protocolo, para quem foi enviado.
            Se a tarefa foi reaberta por engano, use <strong>Reabrir</strong>.</p>

        <h3>5. Ler o relatório do dia</h3>
        <p><strong>O que foi feito</strong>: tarefas finalizadas no dia. <strong>Em andamento</strong>: tarefas que andaram, mas não terminaram.
            <strong>Era para este dia e ficou para depois</strong>: prazos do dia que não foram cumpridos.
            <strong>Primeiro horário</strong> e <strong>Demais tarefas previstas</strong>: o que vence no dia seguinte.
            <strong>Demandas novas</strong>: o que entrou no dia. Use <strong>Copiar relatório</strong> para colar no WhatsApp
            ou <strong>Baixar PDF</strong> para arquivar.</p>
    </article>
@endsection
