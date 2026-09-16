let paginaAtual = 1;
let totalPaginas = 1;
const porPagina = 20;
let buscaAtual = "";
let buscaTimeout = null;

async function carregarAuditoria() {
    try {
        const params = new URLSearchParams({
            pagina: paginaAtual,
            por_pagina: porPagina
        });
        if (buscaAtual) params.set("busca", buscaAtual);

        const response = await fetch(`${API_URL}/auditoria?${params.toString()}`, {
            headers: { "Authorization": "Bearer " + token }
        });

        if (!response.ok) {
            throw new Error("Erro ao buscar dados de auditoria");
        }

        const result = await response.json();
        const dados = result.dados || {};
        const registros = dados.registros || [];
        const paginacao = dados.paginacao || { pagina: 1, total_paginas: 1 };

        paginaAtual = paginacao.pagina;
        totalPaginas = Math.max(1, paginacao.total_paginas);

        renderTable(registros);
        renderPaginacao();

    } catch (error) {
        console.error("Erro ao carregar auditoria", error);
        document.getElementById("auditoriaTable").innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: #ef4444;">
                    Erro ao carregar os dados de auditoria. Verifique sua conexão.
                </td>
            </tr>
        `;
    }
}

function renderTable(data) {
    const tableBody = document.getElementById("auditoriaTable");
    tableBody.innerHTML = "";

    if (data.length === 0) {
        tableBody.innerHTML = "<tr><td colspan='6' style='text-align: center;'>Nenhum registro encontrado.</td></tr>";
        return;
    }

    data.forEach(log => {
        const tr = document.createElement("tr");

        const dataFormatada = new Date(log.data_acao).toLocaleString('pt-BR');
        const paciente = log.paciente_nome ? log.paciente_nome : '<span style="color: var(--text-muted);">Sistema/Geral</span>';
        const acao = `<span class="action-badge">${log.acao}</span>`;

        tr.innerHTML = `
            <td>${dataFormatada}</td>
            <td title="ID: ${log.usuario_id}">${log.email}</td>
            <td>${acao}</td>
            <td>${log.descricao || 'Sem detalhes'}</td>
            <td>${paciente}</td>
            <td><small style="color: var(--text-muted);">${log.ip}</small></td>
        `;
        tableBody.appendChild(tr);
    });
}

function renderPaginacao() {
    const container = document.getElementById("auditoriaPaginacao");
    if (!container) return;

    container.innerHTML = `
        <button class="btn" id="paginaAnterior" ${paginaAtual <= 1 ? 'disabled' : ''}>&laquo; Anterior</button>
        <span style="color: var(--text-muted); font-size: 0.875rem;">Página ${paginaAtual} de ${totalPaginas}</span>
        <button class="btn" id="paginaProxima" ${paginaAtual >= totalPaginas ? 'disabled' : ''}>Próxima &raquo;</button>
    `;

    document.getElementById("paginaAnterior").onclick = () => {
        if (paginaAtual > 1) {
            paginaAtual--;
            carregarAuditoria();
        }
    };
    document.getElementById("paginaProxima").onclick = () => {
        if (paginaAtual < totalPaginas) {
            paginaAtual++;
            carregarAuditoria();
        }
    };
}

// Filtro de busca (server-side, com debounce)
document.getElementById('auditSearch').addEventListener('input', (e) => {
    clearTimeout(buscaTimeout);
    buscaTimeout = setTimeout(() => {
        buscaAtual = e.target.value.trim();
        paginaAtual = 1;
        carregarAuditoria();
    }, 300);
});

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    carregarAuditoria();
});
