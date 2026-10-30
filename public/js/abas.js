// As três abas da aplicação. Cada uma monta os controles uma vez e re-renderiza só os dados,
// para que atualizações em tempo real não atrapalhem quem está digitando.
import { api, aviso, esc, hojeLocal, renderizarPreservandoFoco, somarDias } from './nucleo.js';
import { renderLista } from './linha.js';

const indicador = (rotulo, valor, cor = '') => `
    <div class="indicador"><div class="rot">${esc(rotulo)}</div><div class="num ${cor ? `cor-${cor}` : ''}">${esc(valor)}</div></div>`;

const opcoesUsuarios = (usuarios, atual) => usuarios.map((u) =>
    `<option value="${esc(u.id)}" ${u.id === atual ? 'selected' : ''}>${esc(u.nome)}${u.ativo ? '' : ' (inativo)'}</option>`).join('');

/** Relatório do dia (seções 6.2 e 7.2). */
export class AbaRelatorio {
    constructor(sessao) {
        this.sessao = sessao;
        const p = new URLSearchParams(location.search);
        this.data = /^\d{4}-\d{2}-\d{2}$/.test(p.get('data') ?? '') ? p.get('data') : hojeLocal();
        this.responsavel = p.get('responsavel') ?? '';
        this.dados = null;
    }

    url() {
        const p = new URLSearchParams({ data: this.data });
        if (this.responsavel) p.set('responsavel', this.responsavel);
        return p.toString();
    }

    montar(el) {
        el.innerHTML = `
        <div class="navegacao-dia">
            <button type="button" class="btn" data-rel="anterior" aria-label="Dia anterior">←</button>
            <input type="date" id="rel-data" aria-label="Dia do relatório" value="${esc(this.data)}">
            <button type="button" class="btn" data-rel="proximo" aria-label="Próximo dia">→</button>
            <button type="button" class="btn" data-rel="hoje">Hoje</button>
            <select id="rel-resp" aria-label="Colaborador">
                <option value="">Toda a equipe</option>${opcoesUsuarios(this.sessao.usuarios, this.responsavel)}
            </select>
            <button type="button" class="btn" data-rel="copiar">Copiar relatório</button>
            <a class="btn btn-primario" id="rel-pdf" href="#">Baixar PDF</a>
        </div>
        <div id="rel-copia"></div>
        <div id="rel-conteudo"><p class="carregando">Carregando…</p></div>`;

        el.querySelector('#rel-data').addEventListener('change', (e) => { if (e.target.value) this.irPara(e.target.value); });
        el.querySelector('#rel-resp').addEventListener('change', (e) => { this.responsavel = e.target.value; this.mudou(); });
        el.addEventListener('click', (e) => {
            const acao = e.target.closest('[data-rel]')?.dataset.rel;
            if (acao === 'anterior') this.irPara(somarDias(this.data, -1));
            if (acao === 'proximo') this.irPara(somarDias(this.data, 1));
            if (acao === 'hoje') this.irPara(hojeLocal());
            if (acao === 'copiar') this.copiar();
        });
        this.atualizarControles();
    }

    irPara(dia) {
        this.data = dia;
        this.mudou();
    }

    mudou() {
        history.replaceState(null, '', `/relatorio?${this.url()}`);
        this.atualizarControles();
        this.aoMudar?.();
    }

    atualizarControles() {
        document.getElementById('rel-data').value = this.data;
        document.getElementById('rel-resp').value = this.responsavel;
        document.getElementById('rel-pdf').href = `/relatorio-dia/pdf?${this.url()}`;
    }

    async carregar() {
        this.dados = await api(`/api/relatorio-dia?${this.url()}`);
    }

    renderizar() {
        const d = this.dados;
        const el = document.getElementById('rel-conteudo');
        if (!d || !el) return;
        const i = d.indicadores;

        renderizarPreservandoFoco(el, `
            <h2 class="titulo-dia">${esc(d.titulo)}</h2>
            <div class="indicadores">
                ${indicador('Entraram', i.entraram)}
                ${indicador('Concluídas', i.concluidas, 'verde')}
                ${indicador('Ficaram para depois', i.ficaram, 'vermelho')}
                ${indicador('1º horário amanhã', i.primeiro_horario, 'ambar')}
                ${indicador('Cumprido do previsto', i.cumprido)}
            </div>
            ${d.secoes.map((s) => `
                <section class="secao" aria-label="${esc(s.titulo)}">
                    <h3 class="secao-titulo ${s.cor ? `cor-${s.cor}` : ''}">${esc(s.titulo)} <span class="contador">${s.itens.length}</span></h3>
                    ${s.subtitulo ? `<p class="secao-sub">${esc(s.subtitulo)}</p>` : ''}
                    ${s.itens.length ? renderLista(s.itens, s.chave) : `<div class="vazio">${esc(s.vazio)}</div>`}
                </section>`).join('')}`);
    }

    /** "Copiar relatório": texto para WhatsApp, com alternativa manual se a cópia for bloqueada (8.3). */
    async copiar() {
        const area = document.getElementById('rel-copia');
        try {
            const { texto } = await api(`/api/relatorio-dia/texto?${this.url()}`);
            try {
                await navigator.clipboard.writeText(texto);
                area.innerHTML = '';
                aviso('Relatório copiado. Cole no WhatsApp ou e-mail.');
            } catch {
                area.innerHTML = `<div class="copia-manual"><label class="rotulo" for="rel-texto">Não foi possível copiar automaticamente. Selecione o texto abaixo e copie:</label><textarea id="rel-texto" readonly>${esc(texto)}</textarea></div>`;
                const campo = area.querySelector('textarea');
                campo.focus();
                campo.select();
            }
        } catch (ex) {
            aviso(ex.message);
        }
    }

    /** O formulário de nova tarefa vem com o colaborador filtrado (7.4). */
    responsavelPadrao() {
        return this.responsavel || null;
    }
}

/** Todas as tarefas (seção 7.5). */
export class AbaTarefas {
    constructor(sessao) {
        this.sessao = sessao;
        this.filtros = { busca: '', situacao: '', canal: '', setor: '', responsavel: '' };
        this.paginas = 1;
        this.dados = null;
    }

    montar(el) {
        const s = this.sessao;
        const op = (lista, rotuloTodos) => `<option value="">${rotuloTodos}</option>${lista.map((v) => `<option>${esc(v)}</option>`).join('')}`;
        el.innerHTML = `
        <div id="tar-indicadores" class="indicadores"></div>
        <div class="filtros">
            <input type="search" id="tar-busca" aria-label="Buscar" placeholder="Buscar cliente, descrição ou responsável">
            <select id="tar-situacao" aria-label="Situação">${s.situacoes.map((o) => `<option value="${esc(o.valor)}">${esc(o.rotulo)}</option>`).join('')}</select>
            <select id="tar-canal" aria-label="Canal">${op(s.canais, 'Todos os canais')}</select>
            <select id="tar-setor" aria-label="Setor">${op(s.setores, 'Todos os setores')}</select>
            <select id="tar-responsavel" aria-label="Responsável">
                <option value="">Todos os responsáveis</option><option value="sem">(sem responsável)</option>${opcoesUsuarios(s.usuarios, '')}
            </select>
        </div>
        <div id="tar-lista"><p class="carregando">Carregando…</p></div>`;

        let espera;
        el.querySelector('#tar-busca').addEventListener('input', (e) => {
            clearTimeout(espera);
            espera = setTimeout(() => this.filtrar('busca', e.target.value.trim()), 300);
        });
        for (const campo of ['situacao', 'canal', 'setor', 'responsavel']) {
            el.querySelector(`#tar-${campo}`).addEventListener('change', (e) => this.filtrar(campo, e.target.value));
        }
        el.addEventListener('click', (e) => {
            if (e.target.closest('[data-tar=mais]')) { this.paginas += 1; this.aoMudar?.(); }
        });
    }

    filtrar(campo, valor) {
        this.filtros[campo] = valor;
        this.paginas = 1;
        this.aoMudar?.();
    }

    async carregar() {
        const p = new URLSearchParams({ ...this.filtros, paginas: String(this.paginas) });
        this.dados = await api(`/api/demandas?${p}`);
    }

    renderizar() {
        const d = this.dados;
        if (!d) return;
        const i = d.indicadores;
        document.getElementById('tar-indicadores').innerHTML = `
            ${indicador('Pendentes', i.pendentes)}
            ${indicador('Adiadas', i.adiadas, 'ambar')}
            ${indicador('Pendentes atrasadas', i.atrasadas, 'vermelho')}
            ${indicador('Concluídas no prazo', i.no_prazo, 'verde')}
            ${indicador('Tempo médio de entrega', i.tempo_medio)}`;

        let html;
        if (d.total_geral === 0) {
            html = '<div class="vazio"><strong>Nenhuma tarefa registrada ainda</strong> – Toque em “+ Nova tarefa” para lançar a primeira demanda.</div>';
        } else if (d.total === 0) {
            html = '<div class="vazio"><strong>Nada com esses filtros</strong> – Limpe a busca ou troque os filtros.</div>';
        } else {
            html = renderLista(d.itens, 'lista')
                + (d.tem_mais ? '<div class="carregar-mais"><button type="button" class="btn" data-tar="mais">Carregar mais</button></div>' : '');
        }
        renderizarPreservandoFoco(document.getElementById('tar-lista'), html);
    }

    responsavelPadrao() { return null; }
}

/** Equipe: desempenho por colaborador (seções 6.7 e 7.6). */
export class AbaEquipe {
    constructor(sessao) {
        this.sessao = sessao;
        this.periodo = '30';
        this.dados = null;
    }

    montar(el) {
        el.innerHTML = `
        <div class="equipe-topo">
            <h2>Desempenho por colaborador</h2>
            <select id="eq-periodo" aria-label="Período">
                <option value="7">Últimos 7 dias</option><option value="30" selected>Últimos 30 dias</option>
                <option value="90">Últimos 90 dias</option><option value="tudo">Desde o início</option>
            </select>
        </div>
        <div id="eq-tabela"><p class="carregando">Carregando…</p></div>
        <p class="nota">Recebidas e concluídas contam pelo dia da entrada e da finalização dentro do período. Adiamentos contam cada vez que o prazo foi empurrado. “Em aberto” e “atrasadas” mostram a situação de agora. Toque no nome para abrir o relatório diário daquela pessoa.</p>`;
        el.querySelector('#eq-periodo').value = this.periodo;
        el.querySelector('#eq-periodo').addEventListener('change', (e) => { this.periodo = e.target.value; this.aoMudar?.(); });
    }

    async carregar() {
        this.dados = await api(`/api/equipe?periodo=${encodeURIComponent(this.periodo)}`);
    }

    renderizar() {
        const d = this.dados;
        const el = document.getElementById('eq-tabela');
        if (!d || !el) return;

        if (!d.tem_dados) {
            el.innerHTML = '<div class="vazio"><strong>Sem dados ainda</strong> – O desempenho aparece assim que houver tarefas com responsável.</div>';
            return;
        }

        const nome = (l) => {
            const inativo = l.ativo ? '' : '<span class="selo selo-inativo">inativo</span>';
            return l.id ? `<a href="/relatorio?responsavel=${esc(l.id)}" data-link-aba="relatorio">${esc(l.nome)}</a>${inativo}` : esc(l.nome);
        };

        el.innerHTML = `
        <table class="tabela-equipe">
            <thead><tr><th>Colaborador</th><th>Recebidas</th><th>Concluídas</th><th>No prazo</th><th>Adiamentos</th><th>Tempo médio</th><th>Em aberto</th><th>Atrasadas</th></tr></thead>
            <tbody>${d.linhas.map((l) => `
                <tr>
                    <td>${nome(l)}</td><td>${l.recebidas}</td><td>${l.concluidas}</td>
                    <td class="${l.no_prazo_cor ? `cor-${l.no_prazo_cor}` : ''}"><strong>${esc(l.no_prazo)}</strong></td>
                    <td>${l.adiamentos}</td><td>${esc(l.tempo_medio)}</td><td>${l.em_aberto}</td>
                    <td class="${l.atrasadas > 0 ? 'cor-vermelho' : ''}">${l.atrasadas}</td>
                </tr>`).join('')}
            </tbody>
        </table>`;
    }

    responsavelPadrao() { return null; }
}
