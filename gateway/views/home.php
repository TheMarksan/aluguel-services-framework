<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluguel &middot; Framework Whitelabel</title>
    <style>
        :root {
            --brand: #2563eb; --brand-d: #1d4ed8; --bg: #0f172a; --surface: #1e293b;
            --surface-2: #273449; --text: #e2e8f0; --muted: #94a3b8; --line: #334155;
            --ok: #22c55e; --warn: #f59e0b; --err: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--bg); color: var(--text); line-height: 1.5;
        }
        header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem; border-bottom: 1px solid var(--line);
            position: sticky; top: 0; background: rgba(15,23,42,.9); backdrop-filter: blur(8px);
        }
        header .brand { font-weight: 700; font-size: 1.1rem; }
        header .brand small { color: var(--muted); font-weight: 400; }
        main { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
        section { margin-bottom: 2.5rem; }
        h2 { font-size: 1.2rem; border-left: 3px solid var(--brand); padding-left: .6rem; }
        .row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: flex-end; }
        label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: .25rem; }
        input, select {
            background: var(--surface-2); border: 1px solid var(--line); color: var(--text);
            padding: .55rem .7rem; border-radius: 8px; font-size: .9rem; min-width: 0; width: 100%;
        }
        .field { flex: 1; min-width: 140px; }
        button {
            background: var(--brand); color: #fff; border: none; padding: .6rem 1rem;
            border-radius: 8px; font-weight: 600; cursor: pointer; font-size: .9rem;
        }
        button:hover { background: var(--brand-d); }
        button.ghost { background: transparent; border: 1px solid var(--line); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px,1fr)); gap: 1rem; }
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 1rem; }
        .card h3 { margin: 0 0 .25rem; font-size: 1rem; }
        .card .meta { color: var(--muted); font-size: .85rem; }
        .pill { display: inline-block; font-size: .7rem; padding: .15rem .5rem; border-radius: 999px; background: var(--surface-2); color: var(--muted); }
        .price { color: var(--ok); font-weight: 700; margin-top: .5rem; }
        .status { font-size: .75rem; padding: .15rem .5rem; border-radius: 999px; }
        .status.confirmed { background: rgba(34,197,94,.15); color: var(--ok); }
        .status.pending { background: rgba(245,158,11,.15); color: var(--warn); }
        .status.rejected, .status.cancelled { background: rgba(239,68,68,.15); color: var(--err); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem .6rem; border-bottom: 1px solid var(--line); font-size: .85rem; }
        th { color: var(--muted); }
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem; padding: .8rem 1.1rem;
            border-radius: 10px; background: var(--surface-2); border: 1px solid var(--line);
            max-width: 360px; opacity: 0; transform: translateY(10px); transition: .25s; z-index: 50;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.ok { border-color: var(--ok); }
        .toast.err { border-color: var(--err); }
        dialog {
            background: var(--surface); color: var(--text); border: 1px solid var(--line);
            border-radius: 14px; padding: 1.5rem; width: min(460px, 92vw);
        }
        dialog::backdrop { background: rgba(0,0,0,.6); }
        .muted { color: var(--muted); font-size: .85rem; }
        .stack > * + * { margin-top: .75rem; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <header>
        <div class="brand">Aluguel<small> &middot; Framework Whitelabel</small></div>
        <div id="auth-area" class="row" style="align-items:center;">
            <span id="user-label" class="muted hidden"></span>
            <button id="btn-open-login">Entrar</button>
            <button id="btn-logout" class="ghost hidden">Sair</button>
        </div>
    </header>

    <main>
        <!-- ============ Catalogo ============ -->
        <section>
            <h2>Imoveis disponiveis</h2>
            <div class="row" style="margin: 1rem 0;">
                <div class="field"><label>Cidade</label><input id="f-city" placeholder="Ex.: Maceio"></div>
                <div class="field"><label>Tipo</label>
                    <select id="f-type">
                        <option value="">Todos</option>
                        <option value="casa">Casa</option>
                        <option value="apartamento">Apartamento</option>
                    </select>
                </div>
                <div><button id="btn-filter">Filtrar</button></div>
            </div>
            <div id="properties" class="grid"><p class="muted">Carregando imoveis...</p></div>
        </section>

        <!-- ============ Minhas reservas ============ -->
        <section>
            <h2>Reservas</h2>
            <div id="reservations"><p class="muted">Nenhuma reserva carregada.</p></div>
            <button id="btn-load-res" class="ghost" style="margin-top:.75rem;">Atualizar reservas</button>
        </section>
    </main>

    <!-- ============ Modal de login ============ -->
    <dialog id="login-modal">
        <h2 style="margin-top:0;">Entrar</h2>
        <div class="stack">
            <div><label>E-mail</label><input id="login-email" type="email" value="admin@aluguel.dev"></div>
            <div><label>Senha</label><input id="login-pass" type="password" value="admin123"></div>
            <p class="muted">Use o admin do seed (<code>admin@aluguel.dev / admin123</code>) ou cadastre-se via API.</p>
            <div class="row" style="justify-content:flex-end;">
                <button class="ghost" id="login-cancel" type="button">Cancelar</button>
                <button id="login-submit" type="button">Entrar</button>
            </div>
        </div>
    </dialog>

    <!-- ============ Modal de reserva ============ -->
    <dialog id="reserve-modal">
        <h2 style="margin-top:0;">Nova reserva</h2>
        <div class="stack">
            <p id="reserve-property" class="muted"></p>
            <div><label>Modalidade</label>
                <select id="r-modality">
                    <option value="vacation">Temporada (vacation)</option>
                    <option value="long_term">Longa duracao (long_term)</option>
                </select>
            </div>
            <div class="row">
                <div class="field"><label>Check-in</label><input id="r-checkin" type="date"></div>
                <div class="field"><label>Check-out</label><input id="r-checkout" type="date"></div>
            </div>
            <div id="r-income-wrap" class="hidden">
                <label>Renda mensal (R$) — exigida na longa duracao</label>
                <input id="r-income" type="number" min="0" placeholder="Ex.: 13000">
            </div>
            <div class="row" style="justify-content:flex-end;">
                <button class="ghost" id="reserve-cancel" type="button">Cancelar</button>
                <button id="reserve-submit" type="button">Reservar</button>
            </div>
        </div>
    </dialog>

    <div id="toast" class="toast"></div>

    <script>
    // ====================================================================
    // Front-end stateless: o token JWT vive no localStorage e e enviado
    // no header Authorization. O Gateway repassa tudo para os microsservicos.
    // ====================================================================
    const TOKEN_KEY = 'aluguel_token';
    const USER_KEY  = 'aluguel_user';
    const state = { selectedProperty: null };

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

    // -------------------- Autenticacao --------------------
    function refreshAuthUI() {
        const user = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
        const logged = !!token();
        $('user-label').classList.toggle('hidden', !logged);
        $('btn-logout').classList.toggle('hidden', !logged);
        $('btn-open-login').classList.toggle('hidden', logged);
        if (user) $('user-label').textContent = 'Ola, ' + user.name;
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

    // -------------------- Catalogo --------------------
    async function loadProperties() {
        const params = new URLSearchParams();
        if ($('f-city').value) params.set('city', $('f-city').value);
        if ($('f-type').value) params.set('type', $('f-type').value);
        const box = $('properties');
        box.innerHTML = '<p class="muted">Carregando imoveis...</p>';
        try {
            const { data } = await api('/api/imoveis' + (params.toString() ? '?' + params : ''));
            if (!data || data.length === 0) { box.innerHTML = '<p class="muted">Nenhum imovel encontrado.</p>'; return; }
            box.innerHTML = data.map(p => `
                <div class="card">
                    <span class="pill">${p.type}</span>
                    <h3>${escapeHtml(p.title)}</h3>
                    <div class="meta">${escapeHtml(p.city)} &middot; ${p.bedrooms} quarto(s) &middot; ${p.bathrooms} banheiro(s)</div>
                    <div class="price">R$ ${fmt(p.daily_price)}/dia &middot; R$ ${fmt(p.monthly_price)}/mes</div>
                    <button style="margin-top:.75rem;width:100%;" onclick='openReserve(${JSON.stringify(p)})'>Reservar</button>
                </div>`).join('');
        } catch (e) { box.innerHTML = `<p class="muted">Falha ao carregar: ${e.message}</p>`; }
    }

    // -------------------- Reservas --------------------
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
            toast(`Reserva #${data.id} — status: ${data.status} — R$ ${fmt(data.total_price)}`, 'ok');
            loadReservations();
        } catch (e) { toast(e.message, 'err'); }
    }

    async function loadReservations() {
        const box = $('reservations');
        try {
            const { data } = await api('/api/reservas');
            if (!data || data.length === 0) { box.innerHTML = '<p class="muted">Nenhuma reserva ainda.</p>'; return; }
            box.innerHTML = `<table><thead><tr>
                <th>#</th><th>Imovel</th><th>Modalidade</th><th>Periodo</th><th>Noites</th><th>Total</th><th>Status</th>
                </tr></thead><tbody>${data.map(r => `<tr>
                    <td>${r.id}</td><td>${r.property_id}</td><td>${r.modality}</td>
                    <td>${r.check_in} &rarr; ${r.check_out}</td><td>${r.nights}</td>
                    <td>R$ ${fmt(r.total_price)}</td>
                    <td><span class="status ${r.status}">${r.status}</span></td>
                </tr>`).join('')}</tbody></table>`;
        } catch (e) { box.innerHTML = `<p class="muted">Falha ao carregar reservas: ${e.message}</p>`; }
    }

    // -------------------- Utils --------------------
    const fmt = (v) => Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

    // -------------------- Bindings --------------------
    $('btn-open-login').onclick = () => $('login-modal').showModal();
    $('login-cancel').onclick   = () => $('login-modal').close();
    $('login-submit').onclick   = doLogin;
    $('btn-logout').onclick     = doLogout;
    $('btn-filter').onclick      = loadProperties;
    $('btn-load-res').onclick    = loadReservations;
    $('reserve-cancel').onclick  = () => $('reserve-modal').close();
    $('reserve-submit').onclick  = doReserve;
    $('r-modality').onchange     = toggleIncome;

    refreshAuthUI();
    loadProperties();
    loadReservations();
    </script>
</body>
</html>
