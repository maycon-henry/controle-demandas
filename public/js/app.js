// Ponto de entrada: navegação entre abas, ações das linhas e atualização em tempo real.
import {
    agoraLocal, api, aoSalvarFormularioPendente, aviso, esquecerRascunhos, lembrarRascunho,
    rascunho, rascunhos, recuperarFormularioPendente, sincronizarRelogio, somarDias,
} from './nucleo.js';
import { ui } from './linha.js';
import { AbaEquipe, AbaRelatorio, AbaTarefas } from './abas.js';
import * as formulario from './formulario.js';

const ATUALIZACAO_MS = 5000;   // seção 10: alterações dos outros aparecem em até 5 s
const RECALCULO_MS = 60000;    // seção 6.1: status recalculados a cada 60 s

const ABAS = { relatorio: AbaRelatorio, tarefas: AbaTarefas, equipe: AbaEquipe };
const TITULOS = { relatorio: 'Relatório do dia', tarefas: 'Todas as tarefas', equipe: 'Equipe' };
const CAMPOS_PAINEL = ['para', 'motivo', 'primeiro', 'conclusao', 'fim', 'marcar'];

const vista = document.getElementById('vista');
let sessao;
let aba;
let assinatura = null;
let ultimaCarga = 0;
let carregando = false;
let carregarDeNovo = false;

async function iniciar() {
    sessao = await api('/api/sessao');
    sincronizarRelogio(sessao.agora_ms);
    ui.gestor = sessao.usuario.gestor;
    formulario.configurarFormulario(sessao, () => recarregar());
    aoSalvarFormularioPendente(formulario.valoresAtuais);

    mostrarAba(vista.dataset.abaInicial);
    const pendente = recuperarFormularioPendente();
    if (pendente) formulario.restaurar(pendente);

    ligarEventos();
    setInterval(verificarAtualizacoes, ATUALIZACAO_MS);
}

function mostrarAba(nome) {
    aba = new ABAS[nome](sessao);
    aba.aoMudar = () => recarregar();
    document.querySelectorAll('.aba').forEach((a) => a.classList.toggle('ativa', a.dataset.aba === nome));
    document.title = `${TITULOS[nome]} · Prazo Certo`;
    ui.detalhes.clear();
    ui.painel = null;
    ui.erros.clear();
    aba.montar(vista);
    recarregar();
}

function navegar(url) {
    history.pushState(null, '', url);
    mostrarAba(new URL(url, location.origin).pathname.slice(1));
}

/** Busca os dados da aba atual; chamadas concorrentes viram uma nova carga ao final. */
async function recarregar() {
    if (carregando) { carregarDeNovo = true; return; }
    carregando = true;
    const abaDaCarga = aba;
    try {
        await abaDaCarga.carregar();
        if (abaDaCarga === aba) aba.renderizar();
        ultimaCarga = Date.now();
    } catch (ex) {
        aviso(ex.message);
    } finally {
        carregando = false;
        if (carregarDeNovo) { carregarDeNovo = false; recarregar(); }
    }
}

async function verificarAtualizacoes() {
    if (document.hidden) return;
    try {
        const v = await api('/api/versao');
        sincronizarRelogio(v.agora_ms);
        const mudou = assinatura !== null && v.assinatura !== assinatura;
        assinatura = v.assinatura;
        if (mudou || Date.now() - ultimaCarga >= RECALCULO_MS) recarregar();
    } catch { /* sem rede: tenta de novo no próximo ciclo */ }
}

// ---- Ações das linhas

function demandaPorId(id) {
    const d = aba.dados;
    const listas = d?.secoes ? d.secoes.map((s) => s.itens) : [d?.itens ?? []];
    for (const lista of listas) {
        const achada = lista.find((t) => t.id === id);
        if (achada) return achada;
    }
    return null;
}

const idDaChave = (chave) => chave.slice(chave.indexOf(':') + 1);

function sugestaoAdiamento(d) {
    // Dia seguinte ao prazo atual (se futuro) ou amanhã, às 08h00 (seção 6.3).
    const agora = agoraLocal();
    const base = d.prazo && d.prazo.local > agora ? d.prazo.local.slice(0, 10) : agora.slice(0, 10);
    return `${somarDias(base, 1)}T08:00`;
}

function abrirPainel(chave, tipo) {
    fecharPainel();
    const d = demandaPorId(idDaChave(chave));
    if (!d) return;
    if (tipo === 'adiar') {
        rascunhos.set(`${chave}|para`, sugestaoAdiamento(d));
        rascunhos.set(`${chave}|primeiro`, true);
    }
    if (tipo === 'finalizar') {
        rascunhos.set(`${chave}|fim`, agoraLocal());
        rascunhos.set(`${chave}|marcar`, true);
    }
    ui.painel = { chave, tipo };
    aba.renderizar();
    document.querySelector(`[data-chave="${CSS.escape(chave)}"] [data-painel] input, [data-chave="${CSS.escape(chave)}"] [data-painel] textarea`)
        ?.focus();
}

function fecharPainel() {
    if (!ui.painel) return;
    const { chave } = ui.painel;
    CAMPOS_PAINEL.forEach((c) => rascunhos.delete(`${chave}|${c}`));
    ui.erros.delete(chave);
    ui.painel = null;
}

async function executar(chave, chaveErro, chamada) {
    try {
        const resposta = await chamada();
        ui.erros.delete(chaveErro);
        aviso(resposta.message);
        return true;
    } catch (ex) {
        ui.erros.set(chaveErro, ex.message);
        aba.renderizar();
        return false;
    }
}

const ACOES = {
    'nova-tarefa': () => formulario.abrirNovo(aba.responsavelPadrao()),

    async editar(chave) {
        try {
            formulario.abrirEdicao(await api(`/api/demandas/${idDaChave(chave)}`));
        } catch (ex) { aviso(ex.message); }
    },

    detalhes(chave) {
        if (ui.detalhes.has(chave)) {
            ui.detalhes.delete(chave);
        } else {
            ui.detalhes.add(chave);
            if (!rascunhos.has(`${chave}|acao-em`)) rascunhos.set(`${chave}|acao-em`, agoraLocal());
        }
        aba.renderizar();
    },

    'abrir-adiar': (chave) => abrirPainel(chave, 'adiar'),
    'abrir-finalizar': (chave) => abrirPainel(chave, 'finalizar'),
    'abrir-excluir': (chave) => abrirPainel(chave, 'excluir'),
    'fechar-painel': () => { fecharPainel(); aba.renderizar(); },

    async 'salvar-adiamento'(chave) {
        const para = rascunho(`${chave}|para`);
        if (!para) { ui.erros.set(chave, 'Informe o novo prazo.'); aba.renderizar(); return; }
        const ok = await executar(chave, chave, () => api(`/api/demandas/${idDaChave(chave)}/adiar`, {
            metodo: 'POST',
            corpo: { para, motivo: rascunho(`${chave}|motivo`), primeiro_horario: rascunho(`${chave}|primeiro`, true) },
        }));
        if (ok) { fecharPainel(); recarregar(); }
    },

    async 'salvar-finalizacao'(chave) {
        const conclusao = String(rascunho(`${chave}|conclusao`)).trim();
        const fim = rascunho(`${chave}|fim`);
        const erro = !conclusao ? 'Descreva como a tarefa foi finalizada.' : (!fim ? 'Informe quando foi finalizada.' : null);
        if (erro) { ui.erros.set(chave, erro); aba.renderizar(); return; }
        const ok = await executar(chave, chave, () => api(`/api/demandas/${idDaChave(chave)}/finalizar`, {
            metodo: 'POST', corpo: { conclusao, fim, marcar_etapas: rascunho(`${chave}|marcar`, true) },
        }));
        if (ok) { fecharPainel(); recarregar(); }
    },

    async 'confirmar-exclusao'(chave) {
        const ok = await executar(chave, chave, () => api(`/api/demandas/${idDaChave(chave)}`, { metodo: 'DELETE' }));
        if (ok) { fecharPainel(); ui.detalhes.delete(chave); recarregar(); }
    },

    async reabrir(chave) {
        try {
            aviso((await api(`/api/demandas/${idDaChave(chave)}/reabrir`, { metodo: 'POST' })).message);
            recarregar();
        } catch (ex) { aviso(ex.message); }
    },

    async 'adicionar-etapa'(chave) {
        const texto = String(rascunho(`${chave}|etapa`)).trim();
        if (!texto) { ui.erros.set(`${chave}|detalhes`, 'Descreva a etapa.'); aba.renderizar(); return; }
        const ok = await executar(chave, `${chave}|detalhes`, () => api(`/api/demandas/${idDaChave(chave)}/etapas`, { metodo: 'POST', corpo: { texto } }));
        if (ok) { rascunhos.delete(`${chave}|etapa`); recarregar(); }
    },

    async 'registrar-acao'(chave) {
        const texto = String(rascunho(`${chave}|acao`)).trim();
        const em = rascunho(`${chave}|acao-em`) || agoraLocal();
        if (!texto) { ui.erros.set(`${chave}|detalhes`, 'Descreva a ação realizada.'); aba.renderizar(); return; }
        const ok = await executar(chave, `${chave}|detalhes`, () => api(`/api/demandas/${idDaChave(chave)}/acoes`, { metodo: 'POST', corpo: { em, texto } }));
        if (ok) { rascunhos.delete(`${chave}|acao`); rascunhos.set(`${chave}|acao-em`, agoraLocal()); recarregar(); }
    },
};

async function marcarEtapa(caixa) {
    try {
        await api(`/api/etapas/${caixa.dataset.etapa}`, { metodo: 'PATCH', corpo: { feita: caixa.checked } });
        recarregar();
    } catch (ex) {
        caixa.checked = !caixa.checked;
        aviso(ex.message);
    }
}

function ligarEventos() {
    document.addEventListener('input', (e) => lembrarRascunho(e.target));

    document.addEventListener('change', (e) => {
        lembrarRascunho(e.target);
        if (e.target.dataset.acao === 'marcar-etapa') marcarEtapa(e.target);
    });

    document.addEventListener('keydown', (e) => {
        const alvo = e.target.closest('[data-enter]');
        if (alvo && e.key === 'Enter') { e.preventDefault(); ACOES[alvo.dataset.enter](alvo.dataset.chave); }
    });

    document.addEventListener('click', (e) => {
        const linkAba = e.target.closest('a.aba[data-aba], a[data-link-aba]');
        if (linkAba && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
            e.preventDefault();
            navegar(linkAba.getAttribute('href'));
            return;
        }
        const botao = e.target.closest('button[data-acao]');
        if (botao && ACOES[botao.dataset.acao]) ACOES[botao.dataset.acao](botao.dataset.chave);
    });

    window.addEventListener('popstate', () => mostrarAba(location.pathname.slice(1) in ABAS ? location.pathname.slice(1) : 'relatorio'));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) verificarAtualizacoes(); });
}

iniciar().catch((ex) => {
    vista.innerHTML = `<div class="vazio">${ex.message}</div>`;
});
