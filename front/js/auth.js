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