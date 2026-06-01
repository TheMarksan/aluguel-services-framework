<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluguel &middot; Framework Whitelabel UFAL</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <a class="logo" href="/">
            <span class="logo-mark" aria-hidden="true">&#8962;</span>
            <span>Aluguel<small>Framework Whitelabel &middot; UFAL 2026.1</small></span>
        </a>
        <div class="nav-actions" id="auth-area">
            <span id="user-label" class="user-chip hidden"></span>
            <button id="btn-open-login" class="btn btn-primary btn-sm">Entrar</button>
            <button id="btn-logout" class="btn btn-ghost btn-sm hidden">Sair</button>
        </div>
    </header>

    <!-- Hero + busca (Estatery Search Bar + M-Rent filters) -->
    <section class="hero">
        <div class="hero-inner">
            <h1>Encontre o im&oacute;vel ideal para alugar</h1>
            <p>Busca inteligente com filtros avan&ccedil;ados. Temporada ou longa dura&ccedil;&atilde;o &mdash; tudo pelo mesmo ecossistema.</p>

            <div class="search-panel" id="search-panel">
                <div class="search-tabs" role="tablist">
                    <button type="button" class="search-tab active" data-type="">Todos</button>
                    <button type="button" class="search-tab" data-type="apartamento">Apartamentos</button>
                    <button type="button" class="search-tab" data-type="casa">Casas</button>
                </div>

                <div class="search-row">
                    <div class="search-field">
                        <label for="f-city">Localiza&ccedil;&atilde;o</label>
                        <input id="f-city" type="text" placeholder="Ex.: Macei&oacute;, Marechal Deodoro">
                    </div>
                    <div class="search-field">
                        <label for="f-q">Palavra-chave</label>
                        <input id="f-q" type="text" placeholder="T&iacute;tulo ou endere&ccedil;o">
                    </div>
                    <div class="search-field">
                        <label for="f-available">Disponibilidade</label>
                        <select id="f-available">
                            <option value="">Qualquer</option>
                            <option value="1" selected>Dispon&iacute;veis</option>
                            <option value="0">Indispon&iacute;veis</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <label for="f-sort">Ordenar</label>
                        <select id="f-sort">
                            <option value="newest">Mais recentes</option>
                            <option value="price_asc">Menor di&aacute;ria</option>
                            <option value="price_desc">Maior di&aacute;ria</option>
                            <option value="bedrooms_desc">Mais quartos</option>
                        </select>
                    </div>
                </div>

                <div class="advanced-filters" id="advanced-filters">
                    <div class="search-row">
                        <div class="search-field">
                            <label for="f-min-daily">Di&aacute;ria m&iacute;n. (R$)</label>
                            <input id="f-min-daily" type="number" min="0" step="10" placeholder="0">
                        </div>
                        <div class="search-field">
                            <label for="f-max-daily">Di&aacute;ria m&aacute;x. (R$)</label>
                            <input id="f-max-daily" type="number" min="0" step="10" placeholder="9999">
                        </div>
                        <div class="search-field">
                            <label for="f-bedrooms">Quartos (m&iacute;n.)</label>
                            <select id="f-bedrooms">
                                <option value="">Qualquer</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                                <option value="4">4+</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="f-min-area">&Aacute;rea m&iacute;n. (m&sup2;)</label>
                            <input id="f-min-area" type="number" min="0" step="1" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="search-actions">
                    <div>
                        <button type="button" id="btn-toggle-advanced" class="btn btn-ghost btn-sm">Filtros avan&ccedil;ados</button>
                        <button type="button" id="btn-clear-filters" class="btn btn-ghost btn-sm">Limpar</button>
                    </div>
                    <button type="button" id="btn-search" class="btn btn-primary">Buscar im&oacute;veis</button>
                </div>

                <div class="filter-chips hidden" id="filter-chips"></div>
            </div>
        </div>
    </section>

    <div class="results-bar">
        <div class="results-meta">
            <div class="results-count" id="results-count">Carregando im&oacute;veis&hellip;</div>
            <button type="button" id="btn-refresh" class="btn btn-ghost btn-sm">Atualizar</button>
        </div>
    </div>

    <main>
        <section id="catalog-section">
            <div id="properties" class="property-grid">
                <p class="empty-state">Carregando cat&aacute;logo&hellip;</p>
            </div>
        </section>

        <section style="margin-top: 2.5rem;">
            <div class="section-head">
                <h2>Minhas reservas</h2>
                <button type="button" id="btn-load-res" class="btn btn-ghost btn-sm">Atualizar</button>
            </div>
            <div class="reservations-wrap" id="reservations">
                <p class="empty-state">Nenhuma reserva carregada.</p>
            </div>
        </section>
    </main>

    <!-- Modal login -->
    <dialog id="login-modal">
        <div class="modal-head"><h2>Entrar na plataforma</h2></div>
        <div class="modal-body">
            <label for="login-email">E-mail</label>
            <input id="login-email" type="email" value="admin@aluguel.dev" autocomplete="username">
            <label for="login-pass">Senha</label>
            <input id="login-pass" type="password" value="admin123" autocomplete="current-password">
            <p style="font-size:.82rem;color:var(--muted);margin:0 0 1rem;">Seed: <code>admin@aluguel.dev</code> / <code>admin123</code></p>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="login-cancel">Cancelar</button>
                <button type="button" class="btn btn-primary" id="login-submit">Entrar</button>
            </div>
        </div>
    </dialog>

    <!-- Modal reserva -->
    <dialog id="reserve-modal">
        <div class="modal-head"><h2>Nova reserva</h2></div>
        <div class="modal-body">
            <p id="reserve-property" style="font-size:.9rem;color:var(--muted);margin:0 0 1rem;"></p>
            <label for="r-modality">Modalidade</label>
            <select id="r-modality">
                <option value="vacation">Temporada (vacation)</option>
                <option value="long_term">Longa dura&ccedil;&atilde;o (long_term)</option>
            </select>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div><label for="r-checkin">Check-in</label><input id="r-checkin" type="date"></div>
                <div><label for="r-checkout">Check-out</label><input id="r-checkout" type="date"></div>
            </div>
            <div id="r-income-wrap" class="hidden">
                <label for="r-income">Renda mensal (R$)</label>
                <input id="r-income" type="number" min="0" placeholder="Ex.: 13000">
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="reserve-cancel">Cancelar</button>
                <button type="button" class="btn btn-accent" id="reserve-submit">Confirmar reserva</button>
            </div>
        </div>
    </dialog>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script>
    const TOKEN_KEY = 'aluguel_token';
    const USER_KEY  = 'aluguel_user';
    const state = { selectedProperty: null, allProperties: [], activeType: '' };

    const $ = (id) => document.getElementById(id);
    const token = () => localStorage.getItem(TOKEN_KEY);

    function toast(msg, type = '') {
        const t = $('toast');
        t.textContent = msg;
        t.className = 'toast show ' + type;
        setTimeout(() => (t.className = 'toast ' + type), 3200);
    }

    async function api(path, { method = 'GET', body = null } = {}) {
        const headers = {};
        if (body) headers['Content-Type'] = 'application/json';
        const tk = token();
        if (tk) headers['Authorization'] = 'Bearer ' + tk;
        const res = await fetch(path, { method, headers, body: body ? JSON.stringify(body) : null });
        let data = {};
        try { data = await res.json(); } catch (_) {}
        if (!res.ok) throw new Error(data.error || ('Erro ' + res.status));
        return data;
    }

    const fmt = (v) => Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

    // ---- Auth ----
    function refreshAuthUI() {
        const user = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
        const logged = !!token();
        $('user-label').classList.toggle('hidden', !logged);
        $('btn-logout').classList.toggle('hidden', !logged);
        $('btn-open-login').classList.toggle('hidden', logged);
        if (user) $('user-label').textContent = user.name;
    }

    async function doLogin() {
        try {
            const data = await api('/api/auth/login', {
                method: 'POST',
                body: { email: $('login-email').value, password: $('login-pass').value }
            });
            localStorage.setItem(TOKEN_KEY, data.token);
            localStorage.setItem(USER_KEY, JSON.stringify(data.user));
            $('login-modal').close();
            refreshAuthUI();
            toast('Login efetuado com sucesso.', 'ok');
        } catch (e) { toast(e.message, 'err'); }
    }

    function doLogout() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        refreshAuthUI();
        toast('Sessao encerrada.');
    }

    // ---- Filtros (API + client-side) ----
    function getApiParams() {
        const p = new URLSearchParams();
        const city = $('f-city').value.trim();
        const type = state.activeType || '';
        const avail = $('f-available').value;
        if (city) p.set('city', city);
        if (type) p.set('type', type);
        if (avail !== '') p.set('available', avail);
        return p;
    }

    function applyClientFilters(items) {
        const q = $('f-q').value.trim().toLowerCase();
        const minDaily = parseFloat($('f-min-daily').value) || 0;
        const maxDaily = parseFloat($('f-max-daily').value) || Infinity;
        const minBed = parseInt($('f-bedrooms').value, 10) || 0;
        const minArea = parseFloat($('f-min-area').value) || 0;
        const sort = $('f-sort').value;

        let list = items.filter(p => {
            if (q && !(`${p.title} ${p.address} ${p.city}`.toLowerCase().includes(q))) return false;
            const daily = parseFloat(p.daily_price);
            if (daily < minDaily || daily > maxDaily) return false;
            if (minBed && parseInt(p.bedrooms, 10) < minBed) return false;
            if (minArea && parseFloat(p.area_m2) < minArea) return false;
            return true;
        });

        list.sort((a, b) => {
            switch (sort) {
                case 'price_asc': return parseFloat(a.daily_price) - parseFloat(b.daily_price);
                case 'price_desc': return parseFloat(b.daily_price) - parseFloat(a.daily_price);
                case 'bedrooms_desc': return parseInt(b.bedrooms, 10) - parseInt(a.bedrooms, 10);
                default: return (b.id || 0) - (a.id || 0);
            }
        });
        return list;
    }

    function renderFilterChips() {
        const chips = [];
        if ($('f-city').value.trim()) chips.push(['Cidade', $('f-city').value.trim()]);
        if (state.activeType) chips.push(['Tipo', state.activeType]);
        if ($('f-q').value.trim()) chips.push(['Busca', $('f-q').value.trim()]);
        if ($('f-min-daily').value) chips.push(['Diaria min', 'R$ ' + $('f-min-daily').value]);
        if ($('f-max-daily').value) chips.push(['Diaria max', 'R$ ' + $('f-max-daily').value]);
        if ($('f-bedrooms').value) chips.push(['Quartos', $('f-bedrooms').value + '+']);
        const box = $('filter-chips');
        if (!chips.length) { box.classList.add('hidden'); box.innerHTML = ''; return; }
        box.classList.remove('hidden');
        box.innerHTML = chips.map(([k, v]) => `<span class="chip">${k}: ${escapeHtml(v)}</span>`).join('');
    }

    function renderProperties(list) {
        const box = $('properties');
        $('results-count').innerHTML = `<span>${list.length}</span> imovel(is) encontrado(s)`;

        if (!list.length) {
            box.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><p>Nenhum imovel corresponde aos filtros.</p><button type="button" class="btn btn-ghost btn-sm" onclick="clearFilters()">Limpar filtros</button></div>';
            return;
        }

        box.innerHTML = list.map(p => `
            <article class="property-card">
                <div class="property-thumb">
                    <span class="badge">${escapeHtml(p.type)}</span>
                    <span class="avail ${p.available == 1 ? 'yes' : 'no'}">${p.available == 1 ? 'Disponivel' : 'Indisponivel'}</span>
                </div>
                <div class="property-body">
                    <h3>${escapeHtml(p.title)}</h3>
                    <div class="property-loc">${escapeHtml(p.city)} &middot; ${escapeHtml(p.address)}</div>
                    <div class="property-specs">
                        <span>&#128719; ${p.bedrooms} quarto(s)</span>
                        <span>&#128705; ${p.bathrooms} banh.</span>
                        <span>&#9634; ${parseFloat(p.area_m2).toFixed(0)} m&sup2;</span>
                    </div>
                    <div class="property-prices">
                        <div><span class="price-main">R$ ${fmt(p.daily_price)}</span><span class="price-sub"> /dia</span></div>
                        <div class="price-sub">R$ ${fmt(p.monthly_price)}/mes</div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick='openReserve(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Reservar</button>
                </div>
            </article>`).join('');
    }

    async function loadProperties() {
        $('properties').innerHTML = '<p class="empty-state" style="grid-column:1/-1;">Carregando...</p>';
        renderFilterChips();
        try {
            const params = getApiParams();
            const { data } = await api('/api/imoveis' + (params.toString() ? '?' + params : ''));
            state.allProperties = data || [];
            renderProperties(applyClientFilters(state.allProperties));
        } catch (e) {
            $('properties').innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><p>Falha ao carregar: ${escapeHtml(e.message)}</p><p style="font-size:.85rem;">Verifique se ms-catalogo (:8001) esta ativo.</p></div>`;
            $('results-count').textContent = 'Erro ao buscar';
        }
    }

    window.clearFilters = function () {
        $('f-city').value = '';
        $('f-q').value = '';
        $('f-available').value = '1';
        $('f-sort').value = 'newest';
        $('f-min-daily').value = '';
        $('f-max-daily').value = '';
        $('f-bedrooms').value = '';
        $('f-min-area').value = '';
        state.activeType = '';
        document.querySelectorAll('.search-tab').forEach(t => t.classList.toggle('active', t.dataset.type === ''));
        loadProperties();
    };

    // ---- Reservas ----
    window.openReserve = function (property) {
        if (!token()) { toast('Faca login para reservar.', 'err'); $('login-modal').showModal(); return; }
        state.selectedProperty = property;
        $('reserve-property').textContent = `${property.title} — ${property.city}`;
        toggleIncome();
        $('reserve-modal').showModal();
    };

    function toggleIncome() {
        $('r-income-wrap').classList.toggle('hidden', $('r-modality').value !== 'long_term');
    }

    async function doReserve() {
        const p = state.selectedProperty;
        if (!p) return;
        const body = {
            modality: $('r-modality').value,
            property_id: p.id,
            check_in: $('r-checkin').value,
            check_out: $('r-checkout').value,
            daily_rate: parseFloat(p.daily_price),
            monthly_rate: parseFloat(p.monthly_price),
            extras: {}
        };
        if (body.modality === 'long_term') body.extras.monthly_income = parseFloat($('r-income').value || '0');
        try {
            const { data } = await api('/api/reservas', { method: 'POST', body });
            $('reserve-modal').close();
            toast(`Reserva #${data.id} — ${data.status} — R$ ${fmt(data.total_price)}`, 'ok');
            loadReservations();
        } catch (e) { toast(e.message, 'err'); }
    }

    async function loadReservations() {
        const box = $('reservations');
        try {
            const { data } = await api('/api/reservas');
            if (!data || !data.length) {
                box.innerHTML = '<p class="empty-state">Nenhuma reserva ainda.</p>';
                return;
            }
            box.innerHTML = `<table class="res-table"><thead><tr>
                <th>#</th><th>Imovel</th><th>Modalidade</th><th>Periodo</th><th>Noites</th><th>Total</th><th>Status</th>
            </tr></thead><tbody>${data.map(r => `<tr>
                <td>${r.id}</td><td>#${r.property_id}</td><td>${r.modality}</td>
                <td>${r.check_in} &rarr; ${r.check_out}</td><td>${r.nights}</td>
                <td><strong>R$ ${fmt(r.total_price)}</strong></td>
                <td><span class="status-pill ${r.status}">${r.status}</span></td>
            </tr>`).join('')}</tbody></table>`;
        } catch (e) {
            box.innerHTML = `<p class="empty-state">Falha: ${escapeHtml(e.message)}</p>`;
        }
    }

    // ---- Bindings ----
    document.querySelectorAll('.search-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.activeType = tab.dataset.type;
            loadProperties();
        });
    });

    $('btn-toggle-advanced').onclick = () => {
        $('advanced-filters').classList.toggle('open');
        $('btn-toggle-advanced').textContent = $('advanced-filters').classList.contains('open')
            ? 'Ocultar filtros' : 'Filtros avancados';
    };
    $('btn-search').onclick = loadProperties;
    $('btn-refresh').onclick = loadProperties;
    $('btn-clear-filters').onclick = clearFilters;
    ['f-q','f-min-daily','f-max-daily','f-bedrooms','f-min-area','f-sort'].forEach(id => {
        $(id).addEventListener('keydown', e => { if (e.key === 'Enter') loadProperties(); });
    });

    $('btn-open-login').onclick = () => $('login-modal').showModal();
    $('login-cancel').onclick = () => $('login-modal').close();
    $('login-submit').onclick = doLogin;
    $('btn-logout').onclick = doLogout;
    $('btn-load-res').onclick = loadReservations;
    $('reserve-cancel').onclick = () => $('reserve-modal').close();
    $('reserve-submit').onclick = doReserve;
    $('r-modality').onchange = toggleIncome;

    refreshAuthUI();
    loadProperties();
    loadReservations();
    </script>
</body>
</html>
