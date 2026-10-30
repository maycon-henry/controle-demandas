// Componente "linha da demanda" (seção 7.3), usado em todas as listas, e seus painéis.
import { esc, rascunho } from './nucleo.js';

export const CABECALHO_LISTA = `
    <div class="grade lista-cabecalho" aria-hidden="true">
        <div>Situação</div><div>Cliente e tarefa</div><div>Entrada</div><div>Acordo</div><div>Prazo</div><div>Finalizada</div><div></div>
    </div>`;

/** Estado visual das linhas: painéis abertos e erros, chaveados por "seção:id". */
export const ui = {
    gestor: false,
    detalhes: new Set(),
    painel: null, // { chave, tipo: 'adiar' | 'finalizar' | 'excluir' }
    erros: new Map(),
};

export function renderLista(itens, secao) {
    return `<div class="lista" role="list">${CABECALHO_LISTA}${itens.map((d) => renderLinha(d, `${secao}:${d.id}`)).join('')}</div>`;
}

export function renderLinha(d, chave) {
    const painel = ui.painel?.chave === chave ? ui.painel.tipo : null;
    const abertos = ui.detalhes.has(chave);

    return `
    <div class="linha" role="listitem" data-chave="${esc(chave)}" data-id="${esc(d.id)}">
        <div class="grade">
            <div class="c-status">${seloStatus(d.status)}</div>
            <div class="c-tarefa">
                <div class="cliente">${esc(d.cliente)}</div>
                <div class="descricao">${esc(d.descricao)}</div>
                <div class="metadados">${metadados(d)}</div>
            </div>
            ${celulaData('Entrada', d.entrada)}
            ${celulaData('Acordo', d.acordo)}
            ${celulaPrazo(d)}
            ${celulaFinalizada(d)}
            <div class="c-acoes acoes">${botoes(d, chave, abertos)}</div>
        </div>
        ${painel === 'adiar' ? painelAdiar(chave) : ''}
        ${painel === 'finalizar' ? painelFinalizar(d, chave) : ''}
        ${painel === 'excluir' ? painelExcluir(d, chave) : ''}
        ${abertos ? painelDetalhes(d, chave) : ''}
    </div>`;
}

export function seloStatus(s) {
    return `<span class="status status-${esc(s.cor)}"><strong>${esc(s.titulo)}</strong>${esc(s.complemento)}</span>`;
}

function metadados(d) {
    const partes = [`<span class="selo">${esc(d.canal)}</span>`];
    if (d.primeiro_horario && !d.finalizada) partes.push('<span class="selo selo-escuro">Primeiro horário</span>');
    if (d.adiamentos > 0) partes.push(`<span class="selo selo-ambar">Adiada ${d.adiamentos}x</span>`);
    if (d.etapas.length) partes.push(`<span class="etapas">Etapas ${d.etapas_feitas}/${d.etapas.length}</span>`);
    const info = [d.setor, d.responsavel ?? '(sem responsável)', d.observacoes].filter(Boolean).map(esc).join(' · ');
    partes.push(`<span>${info}</span>`);
    return partes.join('');
}

function celulaData(rotulo, m) {
    return `<div class="data-celula"><span class="rotulo-celula">${rotulo}</span>${m ? `${esc(m.data)}<br><span class="hora">${esc(m.hora)}</span>` : '—'}</div>`;
}

function celulaPrazo(d) {
    const original = d.prazo_original ? `<span class="riscado">${esc(d.prazo_original)}</span>` : '';
    const atual = d.prazo ? `${esc(d.prazo.data)}<br><span class="hora">${esc(d.prazo.hora)}</span>` : '—';
    return `<div class="data-celula"><span class="rotulo-celula">Prazo</span>${original}${atual}</div>`;
}

function celulaFinalizada(d) {
    const valor = d.fim ? `${esc(d.fim.data)}<br><span class="hora">${esc(d.fim.hora)} · ${esc(d.fim.duracao)}</span>` : '—';
    return `<div class="data-celula"><span class="rotulo-celula">Finalizada</span>${valor}</div>`;
}

function botoes(d, chave, abertos) {
    const b = (acao, texto, classe = '') => `<button type="button" class="btn btn-pequeno ${classe}" data-acao="${acao}" data-chave="${esc(chave)}" data-id="${esc(d.id)}">${texto}</button>`;
    const lista = d.finalizada
        ? [b('reabrir', 'Reabrir'), b('editar', 'Editar')]
        : [b('abrir-finalizar', 'Finalizar agora', 'btn-primario'), b('abrir-adiar', 'Adiar'), b('editar', 'Editar')];
    lista.push(b('detalhes', abertos ? 'Fechar detalhes' : 'Detalhes'));
    if (ui.gestor) lista.push(b('abrir-excluir', 'Excluir', 'btn-perigo'));
    return lista.join('');
}

const erroDe = (chave) => (ui.erros.get(chave) ? `<p class="erro" role="alert">${esc(ui.erros.get(chave))}</p>` : '');

function painelAdiar(chave) {
    const k = (c) => `${chave}|${c}`;
    return `
    <div class="painel" data-painel="adiar">
        <div class="grade-campos">
            <div><label class="rotulo" for="${k('para')}">Novo prazo</label>
                <input type="datetime-local" id="${k('para')}" data-rascunho="${k('para')}" value="${esc(rascunho(k('para')))}"></div>
            <div><label class="rotulo" for="${k('motivo')}">Motivo</label>
                <input type="text" id="${k('motivo')}" data-rascunho="${k('motivo')}" value="${esc(rascunho(k('motivo')))}" placeholder="Ex.: cliente não enviou os extratos"></div>
        </div>
        <div class="botoes">
            <label class="caixa"><input type="checkbox" data-rascunho="${k('primeiro')}" ${rascunho(k('primeiro'), true) ? 'checked' : ''}> Fazer no primeiro horário</label>
        </div>
        ${erroDe(chave)}
        <div class="botoes">
            <button type="button" class="btn btn-primario btn-pequeno" data-acao="salvar-adiamento" data-chave="${esc(chave)}">Salvar adiamento</button>
            <button type="button" class="btn btn-pequeno" data-acao="fechar-painel" data-chave="${esc(chave)}">Cancelar</button>
        </div>
    </div>`;
}

function painelFinalizar(d, chave) {
    const k = (c) => `${chave}|${c}`;
    const pendentes = d.etapas.some((e) => !e.feita);
    return `
    <div class="painel" data-painel="finalizar">
        <label class="rotulo" for="${k('conclusao')}">Como foi finalizada</label>
        <textarea id="${k('conclusao')}" data-rascunho="${k('conclusao')}" placeholder="O que foi entregue, protocolo, a quem foi enviado">${esc(rascunho(k('conclusao')))}</textarea>
        <div class="grade-campos" style="margin-top:10px">
            <div><label class="rotulo" for="${k('fim')}">Finalizada em</label>
                <input type="datetime-local" id="${k('fim')}" data-rascunho="${k('fim')}" value="${esc(rascunho(k('fim')))}"></div>
            ${pendentes ? `<div style="align-self:end"><label class="caixa"><input type="checkbox" data-rascunho="${k('marcar')}" ${rascunho(k('marcar'), true) ? 'checked' : ''}> Marcar etapas restantes como feitas</label></div>` : ''}
        </div>
        ${erroDe(chave)}
        <div class="botoes">
            <button type="button" class="btn btn-primario btn-pequeno" data-acao="salvar-finalizacao" data-chave="${esc(chave)}">Concluir tarefa</button>
            <button type="button" class="btn btn-pequeno" data-acao="fechar-painel" data-chave="${esc(chave)}">Cancelar</button>
        </div>
    </div>`;
}

function painelExcluir(d, chave) {
    return `
    <div class="painel painel-perigo" data-painel="excluir">
        <span>Excluir esta tarefa de ${esc(d.cliente)}? Não dá para desfazer.</span>
        <button type="button" class="btn btn-pequeno btn-perigo" data-acao="confirmar-exclusao" data-chave="${esc(chave)}">Excluir</button>
        <button type="button" class="btn btn-pequeno" data-acao="fechar-painel" data-chave="${esc(chave)}">Manter</button>
        ${erroDe(chave)}
    </div>`;
}

function painelDetalhes(d, chave) {
    const k = (c) => `${chave}|${c}`;
    const etapas = d.etapas.map((e) => `
        <label class="etapa ${e.feita ? 'feita' : ''}">
            <input type="checkbox" data-acao="marcar-etapa" data-etapa="${esc(e.id)}" data-chave="${esc(chave)}" ${e.feita ? 'checked' : ''}>
            <span><span class="texto">${esc(e.texto)}</span>${e.feita && e.feita_em ? `<small>${esc(e.feita_em)}</small>` : ''}</span>
        </label>`).join('');
    const acoes = d.acoes.map((a) => `
        <div class="acao-item"><span class="quando">${esc(a.data)}<br>${esc(a.hora)}</span><span>${esc(a.texto)}</span></div>`).join('');
    const historico = d.historico.map((h) => `
        <div><span class="quando">${esc(h.em)}</span><span>${esc(h.texto)}${h.por ? ` <span class="por">— por ${esc(h.por)}</span>` : ''}</span></div>`).join('');
    const erroDetalhes = ui.erros.get(`${chave}|detalhes`);

    return `
    <div class="painel" data-painel="detalhes">
        <div class="bloco-titulo">Demanda solicitada</div>
        <div class="descricao">${esc(d.descricao)}</div>
        <div class="detalhes-colunas">
            <div>
                <div class="bloco-titulo">Etapas ${d.etapas_feitas}/${d.etapas.length}</div>
                ${etapas || '<div class="dica">Nenhuma etapa cadastrada.</div>'}
                <div class="linha-inline">
                    <input type="text" aria-label="Nova etapa" placeholder="Nova etapa" data-rascunho="${k('etapa')}" value="${esc(rascunho(k('etapa')))}" data-enter="adicionar-etapa" data-chave="${esc(chave)}">
                    <button type="button" class="btn btn-pequeno" data-acao="adicionar-etapa" data-chave="${esc(chave)}">Adicionar</button>
                </div>
            </div>
            <div>
                <div class="bloco-titulo">Ações realizadas</div>
                ${acoes || '<div class="dica">Nenhuma ação registrada.</div>'}
                <div class="linha-inline">
                    <input type="datetime-local" aria-label="Quando a ação foi feita" data-rascunho="${k('acao-em')}" value="${esc(rascunho(k('acao-em')))}">
                </div>
                <div class="linha-inline">
                    <input type="text" aria-label="O que foi feito" placeholder="O que foi feito" data-rascunho="${k('acao')}" value="${esc(rascunho(k('acao')))}" data-enter="registrar-acao" data-chave="${esc(chave)}">
                    <button type="button" class="btn btn-pequeno" data-acao="registrar-acao" data-chave="${esc(chave)}">Registrar</button>
                </div>
            </div>
        </div>
        ${erroDetalhes ? `<p class="erro" role="alert">${esc(erroDetalhes)}</p>` : ''}
        <div class="bloco-titulo">Como foi finalizada</div>
        <div class="descricao">${d.conclusao ? esc(d.conclusao) : (d.finalizada ? '—' : 'Ainda não finalizada.')}</div>
        <div class="bloco-titulo">Histórico do sistema</div>
        <div class="historico">${historico}</div>
    </div>`;
}
