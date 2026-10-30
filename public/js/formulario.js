// Formulário "Nova tarefa / Editar tarefa" (seção 7.4). Abre no topo, foca o Cliente.
import { api, agoraLocal, aviso, esc, ErroApi } from './nucleo.js';

const CANAL_OUTRO = 'Outro';
let sessao = null;
let aoSalvar = () => {};
let estadoForm = null; // { modo, id, versao }
let buscaClientes;

export function configurarFormulario(dadosSessao, callbackSalvo) {
    sessao = dadosSessao;
    aoSalvar = callbackSalvo;
}

const alvo = () => document.getElementById('formulario');
export const formularioAberto = () => estadoForm !== null;

/** Valores atuais do formulário aberto (preservados se a sessão expirar). */
export function valoresAtuais() {
    if (!estadoForm) return null;
    return { ...estadoForm, valores: lerCampos() };
}

export function abrirNovo(responsavelPadrao) {
    preencher({ modo: 'novo', id: null, versao: null }, {
        entrada: agoraLocal(), cliente: '', descricao: '', canal: 'WhatsApp', canal_outro: '', setor: sessao.setores[0],
        responsavel_id: responsavelPadrao ?? sessao.usuario.id, acordo_em: '', prazo: '', primeiro_horario: false,
        fim: '', observacoes: '', etapas: '', conclusao: '',
    });
}

export function abrirEdicao(d) {
    preencher({ modo: 'editar', id: d.id, versao: d.versao }, valoresDaDemanda(d));
}

export function restaurar(pendente) {
    preencher({ modo: pendente.modo, id: pendente.id, versao: pendente.versao }, pendente.valores);
}

export function fechar() {
    estadoForm = null;
    alvo().innerHTML = '';
}

function valoresDaDemanda(d) {
    const fixo = sessao.canais.includes(d.canal);
    return {
        entrada: d.entrada?.local ?? '', cliente: d.cliente, descricao: d.descricao,
        canal: fixo ? d.canal : CANAL_OUTRO, canal_outro: fixo ? '' : d.canal,
        setor: d.setor, responsavel_id: d.responsavel_id ?? '', acordo_em: d.acordo?.local ?? '', prazo: d.prazo?.local ?? '',
        primeiro_horario: d.primeiro_horario, fim: d.fim?.local ?? '', observacoes: d.observacoes ?? '',
        etapas: d.etapas.map((e) => e.texto).join('\n'), conclusao: d.conclusao ?? '',
    };
}

function preencher(estado, v) {
    estadoForm = estado;
    const usuarios = sessao.usuarios.filter((u) => u.ativo || u.id === v.responsavel_id);
    const opcao = (valor, texto, atual) => `<option value="${esc(valor)}" ${valor === atual ? 'selected' : ''}>${esc(texto)}</option>`;

    alvo().innerHTML = `
    <form class="formulario" novalidate>
        <h2>${estado.modo === 'novo' ? 'Nova tarefa' : 'Editar tarefa'}</h2>
        <div class="campos">
            <div>
                <label class="rotulo" for="f-entrada">Entrada no escritório (dia e hora)</label>
                <input type="datetime-local" id="f-entrada" name="entrada" value="${esc(v.entrada)}">
                <button type="button" class="link" data-usar-agora="f-entrada">Usar agora</button>
            </div>
            <div>
                <label class="rotulo" for="f-cliente">Cliente</label>
                <input type="text" id="f-cliente" name="cliente" list="f-clientes" autocomplete="off" placeholder="Razão social ou nome fantasia" value="${esc(v.cliente)}">
                <datalist id="f-clientes"></datalist>
            </div>
            <div class="largo">
                <label class="rotulo" for="f-descricao">Descrição da tarefa</label>
                <textarea id="f-descricao" name="descricao" maxlength="5000" placeholder="O que o cliente pediu">${esc(v.descricao)}</textarea>
            </div>
            <div class="largo">
                <span class="rotulo" id="f-canal-rotulo">Como a tarefa chegou</span>
                <div class="pilulas" role="group" aria-labelledby="f-canal-rotulo">
                    ${sessao.canais.map((c) => `<button type="button" class="pilula" data-canal="${esc(c)}" aria-pressed="${c === v.canal}">${esc(c)}</button>`).join('')}
                </div>
                <input type="hidden" name="canal" value="${esc(v.canal)}">
                <div id="f-canal-outro" class="${v.canal === CANAL_OUTRO ? '' : 'oculto'}" style="margin-top:8px">
                    <label class="rotulo" for="f-canal-outro-campo">Qual canal?</label>
                    <input type="text" id="f-canal-outro-campo" name="canal_outro" value="${esc(v.canal_outro)}" placeholder="Ex.: Instagram">
                </div>
            </div>
            <div>
                <label class="rotulo" for="f-setor">Setor</label>
                <select id="f-setor" name="setor">${sessao.setores.map((s) => opcao(s, s, v.setor)).join('')}</select>
            </div>
            <div>
                <label class="rotulo" for="f-responsavel">Responsável</label>
                <select id="f-responsavel" name="responsavel_id">
                    ${opcao('', '(sem responsável)', v.responsavel_id ?? '')}
                    ${usuarios.map((u) => opcao(u.id, u.nome + (u.ativo ? '' : ' (inativo)'), v.responsavel_id)).join('')}
                </select>
            </div>
            <div>
                <label class="rotulo" for="f-acordo">Acordo com o cliente feito em (dia e hora)</label>
                <input type="datetime-local" id="f-acordo" name="acordo_em" value="${esc(v.acordo_em)}">
                <button type="button" class="link" data-usar-agora="f-acordo">Usar agora</button>
            </div>
            <div>
                <label class="rotulo" for="f-prazo">Prazo acordado com o cliente (dia e hora)</label>
                <input type="datetime-local" id="f-prazo" name="prazo" value="${esc(v.prazo)}">
                <div class="dica">Se mudar o prazo de uma tarefa já lançada, o adiamento fica registrado no histórico.</div>
            </div>
            <div class="largo">
                <label class="caixa"><input type="checkbox" name="primeiro_horario" ${v.primeiro_horario ? 'checked' : ''}> Fazer no primeiro horário do dia do prazo</label>
            </div>
            <div>
                <label class="rotulo" for="f-fim">Finalizada em (dia e hora)</label>
                <input type="datetime-local" id="f-fim" name="fim" value="${esc(v.fim)}">
                <div class="dica">Deixe em branco enquanto estiver em andamento.</div>
            </div>
            <div>
                <label class="rotulo" for="f-obs">Observações</label>
                <input type="text" id="f-obs" name="observacoes" maxlength="500" placeholder="Opcional" value="${esc(v.observacoes)}">
            </div>
            <div class="largo">
                <label class="rotulo" for="f-etapas">Etapas da demanda (uma por linha)</label>
                <textarea id="f-etapas" name="etapas" placeholder="Ex.:&#10;Solicitar documentos ao cliente&#10;Conferir certidões&#10;Protocolar na prefeitura">${esc(v.etapas)}</textarea>
            </div>
            <div class="largo">
                <label class="rotulo" for="f-conclusao">Como foi finalizada</label>
                <textarea id="f-conclusao" name="conclusao" placeholder="Preencha ao concluir: o que foi entregue, protocolo, a quem foi enviado">${esc(v.conclusao)}</textarea>
            </div>
        </div>
        <p class="erro" id="f-erro" role="alert" hidden></p>
        <div class="botoes-form">
            <button type="button" class="btn" data-form="cancelar">Cancelar</button>
            <button type="submit" class="btn btn-primario">Salvar tarefa</button>
        </div>
    </form>`;

    ligarEventos();
    alvo().scrollIntoView({ behavior: 'smooth', block: 'start' });
    document.getElementById('f-cliente').focus({ preventScroll: true });
}

function ligarEventos() {
    const form = alvo().querySelector('form');

    form.addEventListener('click', (e) => {
        const pilula = e.target.closest('[data-canal]');
        if (pilula) {
            form.querySelectorAll('[data-canal]').forEach((p) => p.setAttribute('aria-pressed', String(p === pilula)));
            form.elements.canal.value = pilula.dataset.canal;
            document.getElementById('f-canal-outro').classList.toggle('oculto', pilula.dataset.canal !== CANAL_OUTRO);
        }
        const agora = e.target.closest('[data-usar-agora]');
        if (agora) document.getElementById(agora.dataset.usarAgora).value = agoraLocal();
        if (e.target.closest('[data-form=cancelar]')) fechar();
    });

    form.elements.cliente.addEventListener('input', (e) => {
        clearTimeout(buscaClientes);
        const termo = e.target.value.trim();
        if (termo.length < 2) return;
        buscaClientes = setTimeout(async () => {
            const nomes = await api(`/api/clientes?q=${encodeURIComponent(termo)}`).catch(() => []);
            document.getElementById('f-clientes').innerHTML = nomes.map((n) => `<option value="${esc(n)}">`).join('');
        }, 200);
    });

    form.addEventListener('submit', (e) => { e.preventDefault(); salvar(form); });
}

function lerCampos() {
    const f = alvo().querySelector('form').elements;
    return {
        entrada: f.entrada.value, cliente: f.cliente.value, descricao: f.descricao.value, canal: f.canal.value,
        canal_outro: f.canal_outro.value, setor: f.setor.value, responsavel_id: f.responsavel_id.value,
        acordo_em: f.acordo_em.value, prazo: f.prazo.value, primeiro_horario: f.primeiro_horario.checked,
        fim: f.fim.value, observacoes: f.observacoes.value, etapas: f.etapas.value, conclusao: f.conclusao.value,
    };
}

async function salvar(form) {
    const botao = form.querySelector('[type=submit]');
    const erro = document.getElementById('f-erro');
    const valores = lerCampos();
    const corpo = { ...valores, responsavel_id: valores.responsavel_id || null };

    botao.disabled = true;
    erro.hidden = true;
    try {
        const resposta = estadoForm.modo === 'novo'
            ? await api('/api/demandas', { metodo: 'POST', corpo })
            : await api(`/api/demandas/${estadoForm.id}`, { metodo: 'PATCH', corpo: { ...corpo, versao: estadoForm.versao } });
        fechar();
        aviso(resposta.message);
        aoSalvar();
    } catch (ex) {
        if (ex instanceof ErroApi && ex.status === 409) {
            // Conflito: recarrega os dados atuais para a pessoa conferir e salvar de novo.
            const atual = await api(`/api/demandas/${estadoForm.id}`).catch(() => null);
            if (atual) abrirEdicao(atual);
            aoSalvar();
        }
        const alvoErro = document.getElementById('f-erro');
        if (alvoErro) { alvoErro.textContent = ex.message; alvoErro.hidden = false; }
    } finally {
        const b = alvo().querySelector('[type=submit]');
        if (b) b.disabled = false;
    }
}
