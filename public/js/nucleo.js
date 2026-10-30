// Utilitários compartilhados: chamadas à API, avisos, datas no fuso de Brasília e rascunhos.

export const FUSO = 'America/Sao_Paulo';
const CHAVE_FORM_PENDENTE = 'prazocerto:formulario-pendente';

export class ErroApi extends Error {
    constructor(mensagem, status, dados) {
        super(mensagem);
        this.status = status;
        this.dados = dados;
    }
}

const csrf = () => document.querySelector('meta[name=csrf-token]').content;

/** Chamada JSON à API. 401 (sessão terminou) preserva o formulário aberto e volta ao login. */
export async function api(caminho, { metodo = 'GET', corpo } = {}) {
    let resposta;
    try {
        resposta = await fetch(caminho, {
            method: metodo,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: corpo === undefined ? undefined : JSON.stringify(corpo),
            credentials: 'same-origin',
        });
    } catch {
        throw new ErroApi('Não foi possível salvar agora. Tente de novo em instantes.', 0);
    }

    const dados = await resposta.json().catch(() => ({}));

    if (resposta.status === 401 || resposta.status === 419) {
        sessaoTerminou();
        throw new ErroApi('Sua sessão terminou. Entre novamente.', 401);
    }
    if (!resposta.ok) {
        const primeiro = dados.errors ? Object.values(dados.errors)[0]?.[0] : null;
        const mensagem = primeiro || dados.message || 'Não foi possível salvar agora. Tente de novo em instantes.';
        throw new ErroApi(resposta.status >= 500 ? 'Não foi possível salvar agora. Tente de novo em instantes.' : mensagem, resposta.status, dados);
    }
    return dados;
}

let formularioPendente = () => null;
export function aoSalvarFormularioPendente(fn) { formularioPendente = fn; }

function sessaoTerminou() {
    try {
        const pendente = formularioPendente();
        if (pendente) sessionStorage.setItem(CHAVE_FORM_PENDENTE, JSON.stringify(pendente));
    } catch { /* armazenamento indisponível */ }
    aviso('Sua sessão terminou. Entre novamente.');
    setTimeout(() => { location.href = location.pathname + location.search; }, 1200);
}

export function recuperarFormularioPendente() {
    try {
        const bruto = sessionStorage.getItem(CHAVE_FORM_PENDENTE);
        sessionStorage.removeItem(CHAVE_FORM_PENDENTE);
        return bruto ? JSON.parse(bruto) : null;
    } catch { return null; }
}

let temporizadorAviso;
/** Aviso temporário no rodapé central; some em 3 s (T-03). */
export function aviso(texto) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = texto;
    el.classList.add('visivel');
    clearTimeout(temporizadorAviso);
    temporizadorAviso = setTimeout(() => el.classList.remove('visivel'), 3000);
}

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ESCAPES[c]);

// ---- Datas em America/Sao_Paulo, no formato do campo datetime-local ("AAAA-MM-DDTHH:MM")

const partesBrasilia = (data) => Object.fromEntries(
    new Intl.DateTimeFormat('en-CA', { timeZone: FUSO, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
        .formatToParts(data).map((p) => [p.type, p.value]),
);

// Diferença entre o relógio do servidor e o do aparelho: a interface usa o "agora" do servidor.
let deslocamentoMs = 0;
export function sincronizarRelogio(agoraServidorMs) {
    if (Number.isFinite(agoraServidorMs)) deslocamentoMs = agoraServidorMs - Date.now();
}

export function agoraLocal() {
    const p = partesBrasilia(new Date(Date.now() + deslocamentoMs));
    return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`;
}

export const hojeLocal = () => agoraLocal().slice(0, 10);

/** Soma dias a uma data AAAA-MM-DD (aritmética de calendário, sem fuso). */
export function somarDias(dia, n) {
    const [a, m, d] = dia.split('-').map(Number);
    const data = new Date(Date.UTC(a, m - 1, d + n));
    return data.toISOString().slice(0, 10);
}

// ---- Rascunhos: o que o usuário digita sobrevive às atualizações em tempo real

export const rascunhos = new Map();

export function lembrarRascunho(alvo) {
    const chave = alvo.dataset.rascunho;
    if (!chave) return;
    rascunhos.set(chave, alvo.type === 'checkbox' ? alvo.checked : alvo.value);
}

export function rascunho(chave, padrao = '') {
    return rascunhos.has(chave) ? rascunhos.get(chave) : padrao;
}

export function esquecerRascunhos(prefixo) {
    for (const chave of [...rascunhos.keys()]) if (chave.startsWith(prefixo)) rascunhos.delete(chave);
}

/** Re-renderiza um alvo preservando foco e seleção do campo em edição. */
export function renderizarPreservandoFoco(alvo, html) {
    const ativo = document.activeElement;
    const chave = ativo && alvo.contains(ativo) ? ativo.dataset.rascunho : null;
    let selecao = null;
    if (chave && 'selectionStart' in ativo) {
        try { selecao = [ativo.selectionStart, ativo.selectionEnd]; } catch { /* tipos sem seleção */ }
    }

    alvo.innerHTML = html;

    if (chave) {
        const novo = alvo.querySelector(`[data-rascunho="${CSS.escape(chave)}"]`);
        if (novo) {
            novo.focus({ preventScroll: true });
            if (selecao) { try { novo.setSelectionRange(...selecao); } catch { /* idem */ } }
        }
    }
}
