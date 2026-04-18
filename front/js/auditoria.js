let auditLogs = [];

async function carregarAuditoria() {
    try {
        const response = await fetch(`${API_URL}/auditoria`, {
            headers: { "Authorization": "Bearer " + token }
        });
        
        if (!response.ok) {
            throw new Error("Erro ao buscar dados de auditoria");
        }

        const result = await response.json();
        auditLogs = result.dados || [];
        renderTable(auditLogs);
        
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

// Filtro de busca em tempo real
document.getElementById('auditSearch').addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    
    const filtered = auditLogs.filter(log => {
        return (
            log.email?.toLowerCase().includes(term) ||
            log.acao?.toLowerCase().includes(term) ||
            log.descricao?.toLowerCase().includes(term) ||
            log.paciente_nome?.toLowerCase().includes(term)
        );
    });
    
    renderTable(filtered);
});

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    carregarAuditoria();
});
