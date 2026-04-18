async function inicializarDashboard() {
    updateDate();

    try {
        const user = await carregarUsuario();
        if (!user) return;

        renderMenu(user);

        if (document.getElementById('roles-track') || document.getElementById('stat-pacientes')) {
            await loadStats();
        }

        if (document.getElementById('mainChart')) {
            await initCharts();
        }

    } catch (error) {
        console.error("Erro ao carregar dashboard", error);
    }
}

function renderMenu(usuario) {
    const menuContainer = document.getElementById("menu");
    if (!menuContainer) return;

    const hasP = createPermissionChecker(usuario);

    const menuItems = [
        {
            nome: "Dashboard",
            icon: "📊",
            rota: "dashboard.html",
            show: true
        },
        {
            nome: "Usuários",
            icon: "👤",
            rota: "usuarios.html",
            show: hasP("usuario.listar")
        },
        {
            nome: "Papéis",
            icon: "🔐",
            rota: "papeis.html",
            show: hasP("papel.listar")
        },
        /* {
             nome: "Médicos",
             icon: "🩺",
             rota: "medicos.html",
             show: hasP("medico.listar")
         }, */
        {
            nome: "Minha Ficha",
            icon: "🏥",
            rota: `paciente_form.html?id=${usuario.paciente_id}`,
            show: hasP("paciente.visualizar") && usuario.paciente_id !== null
        },
        {
            nome: "Pacientes",
            icon: "🏥",
            rota: "pacientes.html",
            show: hasP("paciente.listar")
        },
        {
            nome: "Diagnóstico IA",
            icon: "🧠",
            rota: "ia.html",
            show: hasP("ia.listar")
        },
        {
            nome: "Chat Médico",
            icon: "💬",
            rota: "chat.html",
            show: hasP("chat.listar")
        },
        {
            nome: "Auditoria",
            icon: "📑",
            rota: "auditoria.html",
            show: hasP("auditoria.listar")
        }
    ];

    const filteredItems = menuItems.filter(item => item.show !== false);

    fetchRolesAndAugmentMenu(filteredItems, menuContainer, hasP, usuario);
}

async function fetchRolesAndAugmentMenu(baseItems, container, hasP, usuario) {
    let finalItems = [...baseItems];

    try {
        // 🔥 admin OU quem pode listar papel
        const podeVerPapeis =
            usuario.admin_owner ||
            usuario.permissoes.some(p => p.endsWith(".listar"));

        if (podeVerPapeis) {

            const res = await fetch("../api/papeis", {
                headers: { "Authorization": "Bearer " + token }
            });

            const result = await res.json();

            if (result.status) {
                let roles = result.dados || [];

                if (roles.length === 0) {
                    roles = [{ id: 1, nome: "ADMIN" }];
                }

                roles.forEach(role => {
                    finalItems.push({
                        nome: role.nome.toUpperCase(),
                        icon: "📌",
                        rota: `usuarios.html?role=${role.id}`,
                        show: true
                    });
                });
            }
        }

    } catch (e) {
        console.error("Erro ao buscar papéis", e);
    }

    renderFinalMenu(finalItems, container);
}

function renderFinalMenu(items, container) {
    container.innerHTML = "";

    items.forEach(item => {
        const isActive =
            location.pathname.includes(item.rota) ||
            (item.rota.includes("role=") && window.location.search.includes(item.rota.split("?")[1]));

        const div = document.createElement("div");

        div.className = `menu-item ${isActive ? 'active' : ''}`;

        div.innerHTML = `
            <span style="margin-right: 12px;">${item.icon}</span>
            ${item.nome}
        `;

        div.style.cssText = `
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            color: var(--text-muted);
        `;

        if (isActive) {
            div.style.background = "var(--glass-bg)";
            div.style.color = "var(--text-main)";
        }

        div.onmouseover = () => div.style.background = "var(--glass-bg)";
        div.onmouseout = () => {
            if (!isActive) div.style.background = "transparent";
        };

        div.onclick = () => window.location.href = item.rota;

        container.appendChild(div);
    });
}

async function loadStats() {
    try {
        const response = await fetch("../api/dashboard/stats", {
            headers: { "Authorization": "Bearer " + token }
        });

        const result = await response.json();
        const stats = result.dados;

        const rolesTrack = document.getElementById('roles-track');
        if (rolesTrack && stats.roles_stats) {
            rolesTrack.innerHTML = '';
            
            // Create slides for roles
            stats.roles_stats.forEach(role => {
                const li = document.createElement('li');
                li.className = 'carousel-slide';
                li.innerHTML = `
                    <div class="card">
                        <div style="color: var(--text-muted); font-size: 0.875rem;">${role.nome}</div>
                        <div class="stat" style="font-size: 2rem; font-weight: 700; margin-top: 0.5rem;">${role.total}</div>
                    </div>
                `;
                rolesTrack.appendChild(li);
            });

            // Initialize carousel logic
            initCarousel();
        }

    } catch (error) {
        console.error("Erro ao carregar estatísticas", error);
    }
}

function initCarousel() {
    const track = document.getElementById('roles-track');
    const slides = Array.from(track.children);
    const nextBtn = document.getElementById('carousel-next');
    const prevBtn = document.getElementById('carousel-prev');

    if (slides.length === 0) return;

    // Minimum slides to make it look like a carousel, clone if needed, but for now just center based on available
    let currentIndex = Math.floor(slides.length / 2); // Start at the middle

    const updateCarousel = () => {
        slides.forEach((slide, index) => {
            slide.classList.remove('current-slide');
            if (index === currentIndex) {
                slide.classList.add('current-slide');
            }
        });

        // 33.3333% is the width of one slide
        // To center the current slide, we shift left depending on current index
        // Formula: translation = -1 * (currentIndex - 1) * 33.3333 
        // If index=0, we shift +33.3333%
        // If index=1, we shift 0%
        // If index=2, we shift -33.3333%
        const offset = - (currentIndex - 1) * 33.3333;
        track.style.transform = `translateX(${offset}%)`;
    };

    nextBtn.onclick = () => {
        if (currentIndex < slides.length - 1) {
            currentIndex++;
        } else {
            currentIndex = 0; // wrap around
        }
        updateCarousel();
    };

    prevBtn.onclick = () => {
        if (currentIndex > 0) {
            currentIndex--;
        } else {
            currentIndex = slides.length - 1; // wrap around
        }
        updateCarousel();
    };

    // Initial setup
    updateCarousel();
}

let isChartsLoading = false;

async function initCharts() {
    if (isChartsLoading) return;
    isChartsLoading = true;

    try {
        const response = await fetch("../api/dashboard/charts", {
            headers: { "Authorization": "Bearer " + token }
        });

        const result = await response.json();
        const apiData = result.dados;

        const labels = apiData.length > 0
            ? apiData.map(d => `Mês ${d.mes}`)
            : ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];

        const values = apiData.length > 0
            ? apiData.map(d => d.total)
            : [0, 0, 0, 0, 0, 0];

        const mainEl = document.getElementById('mainChart');

        if (mainEl) {
            const existingChart = Chart.getChart(mainEl);
            if (existingChart) existingChart.destroy();

            new Chart(mainEl, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Diagnósticos',
                        data: values,
                        borderColor: '#6366f1',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(99, 102, 241, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }

    } catch (error) {
        console.error("Erro ao carregar gráficos", error);
    }

    isChartsLoading = false;
}

function logout() {
    localStorage.clear();
    location.href = "../index.html";
}

function updateDate() {
    const el = document.getElementById('currentDate');

    if (el) {
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };

        el.innerText = new Date().toLocaleDateString('pt-BR', options);
    }
}

inicializarDashboard();