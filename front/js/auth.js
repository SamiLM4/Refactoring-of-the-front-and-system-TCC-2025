const API_URL = "../api";
const token = localStorage.getItem("access_token");

// Redirect if no token, except on login page
if (!token && !window.location.pathname.includes("index.html") && !window.location.pathname.includes("register_institution.html")) {
    window.location.href = "../index.html";
}

let usuario = null;
let permissoes = [];
let _usuarioPromise = null;

async function carregarUsuario() {
    if (!token) return;
    if (_usuarioPromise) return _usuarioPromise;

    _usuarioPromise = (async () => {
        try {
            const response = await fetch(`${API_URL}/auth/me`, {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                }
            });

            if (response.status === 401) {
                logout();
                return null;
            }

            const result = await response.json();
            usuario = result.dados || result; // Fallback if result is the user object itself

            if (usuario.admin_owner) {
                permissoes = ["*"];
            } else {
                permissoes = usuario.permissoes || [];
            }

            const userEmailEl = document.getElementById("userEmail");
            if (userEmailEl) userEmailEl.innerText = usuario.email;

            return usuario;
        } catch (error) {
            console.error("Erro ao carregar usuário:", error);
            _usuarioPromise = null;
            return null;
        }
    })();
    
    return _usuarioPromise;
}

function temPermissao(recurso, acao) {
    if (permissoes.includes("*")) return true;

    return (permissoes || []).some(p => {
        const [recursoPerm, acaoPerm] = p.split(".");

        return recursoPerm.startsWith(recurso) && acaoPerm === acao;
    });
}

function logout() {
    localStorage.clear();
    window.location.href = "../index.html";
}

function showToast(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#6366f1');
    toast.style.cssText = `background-color: ${bgColor}; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-size: 14px; opacity: 0; transform: translateY(20px); transition: all 0.3s ease; pointer-events: auto;`;
    toast.innerText = msg;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Intercept alerts to show modern toasts instead of blocking modals
window.alert = function(msg) {
    const isError = typeof msg === 'string' && (msg.toLowerCase().includes('erro') || msg.toLowerCase().includes('falha') || msg.toLowerCase().includes('selecione'));
    showToast(msg, isError ? 'error' : 'success');
};