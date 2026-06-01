/**
 * Aluguel Framework — Front-end SPA (Gateway)
 * Views: explorar, detalhe, meus-anuncios, reservas
 */
const TOKEN_KEY = 'aluguel_token';
const USER_KEY  = 'aluguel_user';

const state = {
    allProperties: [],
    activeType: '',
    selectedProperty: null,
    editingListingId: null,
    createStep: 1,
    bounds: { daily: [0, 10000], monthly: [0, 50000], area: [0, 500] },
};

const $ = (id) => document.getElementById(id);
const token = () => localStorage.getItem(TOKEN_KEY);
const user = () => JSON.parse(localStorage.getItem(USER_KEY) || 'null');
const fmt = (v) => Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const escapeHtml = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

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

// ---- Routing ----
function parseRoute() {
    const hash = location.hash.replace(/^#/, '') || '/';
    const parts = hash.split('/').filter(Boolean);
    if (parts[0] === 'imovel' && parts[1]) return { view: 'detail', id: parseInt(parts[1], 10) };
    if (parts[0] === 'meus-anuncios') return { view: 'listings' };
    if (parts[0] === 'reservas') return { view: 'reservations' };
    return { view: 'explore' };
}

function navigate(view, id) {
    if (view === 'detail' && id) location.hash = `#/imovel/${id}`;
    else if (view === 'listings') location.hash = '#/meus-anuncios';
    else if (view === 'reservations') location.hash = '#/reservas';
    else location.hash = '#/';
    renderRoute();
}

function renderRoute() {
    const route = parseRoute();
    document.querySelectorAll('.app-view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('[data-nav]').forEach(b => {
        b.classList.toggle('active', b.dataset.nav === route.view);
    });

    $('hero-section').classList.toggle('hidden', route.view !== 'explore');
    $('results-bar').classList.toggle('hidden', route.view !== 'explore');

    if (route.view === 'explore') {
        $('view-explore').classList.add('active');
    } else if (route.view === 'detail') {
        $('view-detail').classList.add('active');
        loadPropertyDetail(route.id);
    } else if (route.view === 'listings') {
        $('view-listings').classList.add('active');
        loadMyListings();
    } else if (route.view === 'reservations') {
        $('view-reservations').classList.add('active');
        loadReservations();
    }
}

// ---- Auth ----
function refreshAuthUI() {
    const u = user();
    const logged = !!token();
    $('user-label').classList.toggle('hidden', !logged);
    $('btn-logout').classList.toggle('hidden', !logged);
    $('btn-open-login').classList.toggle('hidden', logged);
    $('btn-create-listing').classList.toggle('hidden', !logged);
    if (u) $('user-label').textContent = u.name;
}

async function doLogin() {
    try {
        const data = await api('/api/auth/login', {
            method: 'POST',
            body: { email: $('login-email').value, password: $('login-pass').value },
        });
        localStorage.setItem(TOKEN_KEY, data.token);
        localStorage.setItem(USER_KEY, JSON.stringify(data.user));
        $('login-modal').close();
        refreshAuthUI();
        toast('Login efetuado.', 'ok');
        if (parseRoute().view === 'listings') loadMyListings();
    } catch (e) { toast(e.message, 'err'); }
}

function doLogout() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    refreshAuthUI();
    navigate('explore');
    toast('Sessao encerrada.');
}

function requireLogin(action) {
    if (!token()) { toast('Faca login para continuar.', 'err'); $('login-modal').showModal(); return false; }
    return true;
}

// ---- Range filters ----
function initRange(idMin, idMax, idLbl, bounds, suffix = '') {
    const minEl = $(idMin), maxEl = $(idMax), lbl = $(idLbl);
    [minEl, maxEl].forEach(el => {
        el.min = bounds[0]; el.max = bounds[1]; el.step = Math.max(1, Math.round((bounds[1] - bounds[0]) / 100));
    });
    minEl.value = bounds[0]; maxEl.value = bounds[1];
    const update = () => {
        let lo = parseFloat(minEl.value), hi = parseFloat(maxEl.value);
        if (lo > hi) { [lo, hi] = [hi, lo]; minEl.value = lo; maxEl.value = hi; }
        lbl.textContent = suffix + fmt(lo) + ' — ' + suffix + fmt(hi);
    };
    minEl.oninput = maxEl.oninput = update;
    update();
}

function updateBoundsFromData(items) {
    if (!items.length) return;
    const daily = items.map(p => parseFloat(p.daily_price));
    const monthly = items.map(p => parseFloat(p.monthly_price));
    const area = items.map(p => parseFloat(p.area_m2));
    state.bounds.daily = [0, Math.ceil(Math.max(...daily) / 50) * 50 || 1000];
    state.bounds.monthly = [0, Math.ceil(Math.max(...monthly) / 100) * 100 || 10000];
    state.bounds.area = [0, Math.ceil(Math.max(...area) / 10) * 10 || 300];
    initRange('f-daily-min', 'f-daily-max', 'f-daily-lbl', state.bounds.daily, 'R$ ');
    initRange('f-monthly-min', 'f-monthly-max', 'f-monthly-lbl', state.bounds.monthly, 'R$ ');
    initRange('f-area-min', 'f-area-max', 'f-area-lbl', state.bounds.area, '');
}

function getApiParams() {
    const p = new URLSearchParams();
    const city = $('f-city').value.trim();
    if (city) p.set('city', city);
    if (state.activeType) p.set('type', state.activeType);
    const avail = $('f-available').value;
    if (avail !== '') p.set('available', avail);
    return p;
}

function applyClientFilters(items) {
    const q = $('f-q').value.trim().toLowerCase();
    const minDaily = parseFloat($('f-daily-min').value) || 0;
    const maxDaily = parseFloat($('f-daily-max').value) || Infinity;
    const minMonthly = parseFloat($('f-monthly-min')?.value) || 0;
    const maxMonthly = parseFloat($('f-monthly-max')?.value) || Infinity;
    const minArea = parseFloat($('f-area-min').value) || 0;
    const maxArea = parseFloat($('f-area-max').value) || Infinity;
    const minBed = parseInt($('f-bedrooms-min').value, 10) || 0;
    const maxBed = parseInt($('f-bedrooms-max').value, 10) || 99;
    const minBath = parseInt($('f-bathrooms-min').value, 10) || 0;
    const sort = $('f-sort').value;

    let list = items.filter(p => {
        const text = `${p.title} ${p.address} ${p.city} ${p.description || ''}`.toLowerCase();
        if (q && !text.includes(q)) return false;
        const daily = parseFloat(p.daily_price);
        const monthly = parseFloat(p.monthly_price);
        const area = parseFloat(p.area_m2);
        const beds = parseInt(p.bedrooms, 10);
        const baths = parseInt(p.bathrooms, 10);
        if (daily < minDaily || daily > maxDaily) return false;
        if (monthly < minMonthly || monthly > maxMonthly) return false;
        if (area < minArea || area > maxArea) return false;
        if (beds < minBed || beds > maxBed) return false;
        if (baths < minBath) return false;
        return true;
    });

    list.sort((a, b) => {
        switch (sort) {
            case 'price_asc': return parseFloat(a.daily_price) - parseFloat(b.daily_price);
            case 'price_desc': return parseFloat(b.daily_price) - parseFloat(a.daily_price);
            case 'area_desc': return parseFloat(b.area_m2) - parseFloat(a.area_m2);
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
    chips.push(['Diaria', $('f-daily-lbl').textContent]);
    chips.push(['Mensal', $('f-monthly-lbl').textContent]);
    const box = $('filter-chips');
    box.classList.remove('hidden');
    box.innerHTML = chips.map(([k, v]) => `<span class="chip">${k}: ${escapeHtml(v)}</span>`).join('')
        + `<span class="chip"><button type="button" onclick="clearFilters()" title="Limpar">&times;</button> Limpar tudo</span>`;
}

function propertyCardHtml(p) {
    return `
    <article class="property-card">
        <div class="property-thumb" role="button" tabindex="0" onclick="navigate('detail',${p.id})">
            <span class="badge">${escapeHtml(p.type)}</span>
            <span class="avail ${p.available == 1 ? 'yes' : 'no'}">${p.available == 1 ? 'Disponivel' : 'Indisponivel'}</span>
        </div>
        <div class="property-body">
            <h3><a href="#/imovel/${p.id}" style="color:inherit;text-decoration:none">${escapeHtml(p.title)}</a></h3>
            <div class="property-loc">${escapeHtml(p.city)} &middot; ${escapeHtml(p.address)}</div>
            <div class="property-specs">
                <span>&#128719; ${p.bedrooms} q.</span>
                <span>&#128705; ${p.bathrooms} banh.</span>
                <span>&#9634; ${parseFloat(p.area_m2).toFixed(0)} m&sup2;</span>
            </div>
            <div class="property-prices">
                <div><span class="price-main">R$ ${fmt(p.daily_price)}</span><span class="price-sub"> /dia</span></div>
                <div class="price-sub">R$ ${fmt(p.monthly_price)}/mes</div>
            </div>
            <div class="card-actions">
                <button type="button" class="btn btn-ghost" onclick="navigate('detail',${p.id})">Detalhes</button>
                <button type="button" class="btn btn-primary" onclick='openReserve(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Reservar</button>
            </div>
        </div>
    </article>`;
}

function renderProperties(list) {
    const box = $('properties');
    $('results-count').innerHTML = `<span>${list.length}</span> imovel(is) encontrado(s)`;
    if (!list.length) {
        box.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><p>Nenhum imovel corresponde aos filtros.</p><button type="button" class="btn btn-ghost btn-sm" onclick="clearFilters()">Limpar filtros</button></div>';
        return;
    }
    box.innerHTML = list.map(propertyCardHtml).join('');
}

async function loadProperties() {
    $('properties').innerHTML = '<p class="empty-state" style="grid-column:1/-1;">Carregando...</p>';
    try {
        const params = getApiParams();
        const { data } = await api('/api/imoveis' + (params.toString() ? '?' + params : ''));
        state.allProperties = data || [];
        updateBoundsFromData(state.allProperties);
        renderFilterChips();
        renderProperties(applyClientFilters(state.allProperties));
    } catch (e) {
        $('properties').innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><p>Falha: ${escapeHtml(e.message)}</p></div>`;
    }
}

function clearFilters() {
    $('f-city').value = ''; $('f-q').value = '';
    $('f-available').value = '1'; $('f-sort').value = 'newest';
    $('f-bedrooms-min').value = '0'; $('f-bedrooms-max').value = '10';
    $('f-bathrooms-min').value = '0';
    state.activeType = '';
    document.querySelectorAll('.search-tab').forEach(t => t.classList.toggle('active', t.dataset.type === ''));
    if (state.allProperties.length) updateBoundsFromData(state.allProperties);
    loadProperties();
}

// ---- Property detail ----
async function loadPropertyDetail(id) {
    const box = $('detail-content');
    box.innerHTML = '<p class="empty-state">Carregando imovel...</p>';
    try {
        const { data: p } = await api(`/api/imoveis/${id}`);
        state.selectedProperty = p;
        box.innerHTML = `
        <button type="button" class="btn btn-ghost detail-back" onclick="navigate('explore')">&larr; Voltar</button>
        <div class="detail-hero">
            <span class="badge">${escapeHtml(p.type)}</span>
            <span class="avail ${p.available == 1 ? 'yes' : 'no'}" style="position:absolute;top:1rem;right:1rem">${p.available == 1 ? 'Disponivel' : 'Indisponivel'}</span>
        </div>
        <div class="detail-layout">
            <div class="detail-main">
                <h1>${escapeHtml(p.title)}</h1>
                <p class="property-loc">${escapeHtml(p.city)} &mdash; ${escapeHtml(p.address)}</p>
                <div class="detail-specs">
                    <div class="spec-box"><strong>${p.bedrooms}</strong><span>Quartos</span></div>
                    <div class="spec-box"><strong>${p.bathrooms}</strong><span>Banheiros</span></div>
                    <div class="spec-box"><strong>${parseFloat(p.area_m2).toFixed(0)}</strong><span>m&sup2;</span></div>
                    <div class="spec-box"><strong>#${p.id}</strong><span>Codigo</span></div>
                </div>
                <h3 style="margin:0 0 .5rem;font-size:1rem;">Descricao</h3>
                <p class="detail-desc">${escapeHtml(p.description || 'Sem descricao cadastrada.')}</p>
            </div>
            <aside class="detail-sidebar">
                <div class="price-main" style="font-size:1.5rem;margin-bottom:.25rem">R$ ${fmt(p.daily_price)}<span class="price-sub"> /dia</span></div>
                <div class="price-sub" style="margin-bottom:1rem">R$ ${fmt(p.monthly_price)} / mes</div>
                <button type="button" class="btn btn-accent" style="width:100%;margin-bottom:.5rem" onclick='openReserve(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Reservar agora</button>
                ${canManageListing(p) ? `<button type="button" class="btn btn-ghost" style="width:100%" onclick="openEditListing(${p.id})">Editar anuncio</button>` : ''}
            </aside>
        </div>`;
    } catch (e) {
        box.innerHTML = `<div class="empty-state"><p>${escapeHtml(e.message)}</p><button class="btn btn-ghost" onclick="navigate('explore')">Voltar</button></div>`;
    }
}

function canManageListing(p) {
    const u = user();
    return u && (u.role === 'admin' || u.role === 'locador') && parseInt(p.owner_id, 10) === parseInt(u.id, 10);
}

// ---- Meus anuncios ----
async function loadMyListings() {
    if (!requireLogin()) {
        $('listings-content').innerHTML = '<p class="empty-state">Faca login para ver seus anuncios.</p>';
        return;
    }
    const u = user();
    const box = $('listings-content');
    box.innerHTML = '<p class="empty-state">Carregando...</p>';
    try {
        const { data } = await api('/api/imoveis');
        const mine = (data || []).filter(p => parseInt(p.owner_id, 10) === parseInt(u.id, 10));
        const avail = mine.filter(p => p.available == 1).length;
        $('listings-stats').innerHTML = `
            <div class="stat-card"><strong>${mine.length}</strong><span>Total de anuncios</span></div>
            <div class="stat-card"><strong>${avail}</strong><span>Disponiveis</span></div>
            <div class="stat-card"><strong>${mine.length - avail}</strong><span>Indisponiveis</span></div>`;
        if (!mine.length) {
            box.innerHTML = '<p class="empty-state">Voce ainda nao publicou anuncios.<br><button class="btn btn-primary btn-sm" style="margin-top:1rem" onclick="openCreateListing()">Criar primeiro anuncio</button></p>';
            return;
        }
        box.innerHTML = mine.map(p => `
            <div class="listing-row">
                <div>
                    <h4>${escapeHtml(p.title)}</h4>
                    <div class="meta">${escapeHtml(p.city)} &middot; ${escapeHtml(p.type)} &middot; R$ ${fmt(p.daily_price)}/dia</div>
                </div>
                <span class="status-pill ${p.available == 1 ? 'confirmed' : 'pending'}">${p.available == 1 ? 'Ativo' : 'Pausado'}</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="navigate('detail',${p.id})">Ver</button>
                <div style="display:flex;gap:.35rem">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openEditListing(${p.id})">Editar</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleListingAvailability(${p.id},${p.available == 1 ? 0 : 1})">${p.available == 1 ? 'Pausar' : 'Ativar'}</button>
                </div>
            </div>`).join('');
    } catch (e) {
        box.innerHTML = `<p class="empty-state">${escapeHtml(e.message)}</p>`;
    }
}

async function toggleListingAvailability(id, available) {
    if (!requireLogin()) return;
    try {
        const { data: current } = await api(`/api/imoveis/${id}`);
        await api(`/api/imoveis/${id}`, { method: 'PUT', body: { ...current, available: !!available } });
        toast(available ? 'Anuncio ativado.' : 'Anuncio pausado.', 'ok');
        loadMyListings();
        loadProperties();
    } catch (e) { toast(e.message, 'err'); }
}

// ---- Criar / editar anuncio ----
function openCreateListing() {
    if (!requireLogin()) return;
    state.editingListingId = null;
    state.createStep = 1;
    $('listing-modal-title').textContent = 'Criar anuncio';
    clearListingForm();
    updateListingSteps();
    $('listing-modal').showModal();
}

async function openEditListing(id) {
    if (!requireLogin()) return;
    try {
        const { data: p } = await api(`/api/imoveis/${id}`);
        state.editingListingId = id;
        state.createStep = 1;
        $('listing-modal-title').textContent = 'Editar anuncio';
        fillListingForm(p);
        updateListingSteps();
        $('listing-modal').showModal();
    } catch (e) { toast(e.message, 'err'); }
}

function clearListingForm() {
    ['l-title','l-desc','l-city','l-address','l-area','l-daily','l-monthly'].forEach(id => $(id).value = '');
    $('l-type').value = 'apartamento';
    $('l-bedrooms').value = '2';
    $('l-bathrooms').value = '1';
    $('l-available').value = '1';
}

function fillListingForm(p) {
    $('l-title').value = p.title || '';
    $('l-desc').value = p.description || '';
    $('l-type').value = p.type || 'apartamento';
    $('l-city').value = p.city || '';
    $('l-address').value = p.address || '';
    $('l-bedrooms').value = p.bedrooms || 1;
    $('l-bathrooms').value = p.bathrooms || 1;
    $('l-area').value = p.area_m2 || '';
    $('l-daily').value = p.daily_price || '';
    $('l-monthly').value = p.monthly_price || '';
    $('l-available').value = p.available == 1 ? '1' : '0';
}

function listingPayload() {
    const u = user();
    return {
        owner_id: u?.id || null,
        title: $('l-title').value.trim(),
        description: $('l-desc').value.trim(),
        type: $('l-type').value,
        city: $('l-city').value.trim(),
        address: $('l-address').value.trim(),
        bedrooms: parseInt($('l-bedrooms').value, 10) || 1,
        bathrooms: parseInt($('l-bathrooms').value, 10) || 1,
        area_m2: parseFloat($('l-area').value) || 0,
        daily_price: parseFloat($('l-daily').value) || 0,
        monthly_price: parseFloat($('l-monthly').value) || 0,
        available: $('l-available').value === '1',
    };
}

function updateListingSteps() {
    document.querySelectorAll('.modal-step').forEach(s => {
        const step = parseInt(s.dataset.step, 10);
        s.classList.toggle('active', step === state.createStep);
        s.classList.toggle('done', step < state.createStep);
    });
    $('listing-step-1').classList.toggle('hidden', state.createStep !== 1);
    $('listing-step-2').classList.toggle('hidden', state.createStep !== 2);
    $('listing-btn-prev').classList.toggle('hidden', state.createStep === 1);
    $('listing-btn-next').classList.toggle('hidden', state.createStep === 2);
    $('listing-btn-save').classList.toggle('hidden', state.createStep !== 2);
}

async function saveListing() {
    const body = listingPayload();
    if (!body.title || !body.city || !body.address) {
        toast('Preencha titulo, cidade e endereco.', 'err');
        state.createStep = 1;
        updateListingSteps();
        return;
    }
    try {
        if (state.editingListingId) {
            await api(`/api/imoveis/${state.editingListingId}`, { method: 'PUT', body });
            toast('Anuncio atualizado.', 'ok');
        } else {
            await api('/api/imoveis', { method: 'POST', body });
            toast('Anuncio publicado.', 'ok');
        }
        $('listing-modal').close();
        loadProperties();
        if (parseRoute().view === 'listings') loadMyListings();
    } catch (e) { toast(e.message, 'err'); }
}

// ---- Reservas ----
function openReserve(property) {
    if (!requireLogin()) return;
    state.selectedProperty = property;
    $('reserve-property').textContent = `${property.title} — ${property.city}`;
    $('reserve-summary').innerHTML = `
        <div style="background:var(--surface-2);padding:.75rem;border-radius:8px;font-size:.88rem;margin-bottom:1rem">
            <strong>R$ ${fmt(property.daily_price)}</strong>/dia &middot; R$ ${fmt(property.monthly_price)}/mes
        </div>`;
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
        user_id: user()?.id || null,
        check_in: $('r-checkin').value,
        check_out: $('r-checkout').value,
        daily_rate: parseFloat(p.daily_price),
        monthly_rate: parseFloat(p.monthly_price),
        extras: {},
    };
    if (body.modality === 'long_term') body.extras.monthly_income = parseFloat($('r-income').value || '0');
    try {
        const { data } = await api('/api/reservas', { method: 'POST', body });
        $('reserve-modal').close();
        toast(`Reserva #${data.id} — ${data.status} — R$ ${fmt(data.total_price)}`, 'ok');
        if (parseRoute().view === 'reservations') loadReservations();
    } catch (e) { toast(e.message, 'err'); }
}

async function loadReservations() {
    const box = $('reservations');
    try {
        const { data } = await api('/api/reservas');
        if (!data?.length) { box.innerHTML = '<p class="empty-state">Nenhuma reserva ainda.</p>'; return; }
        box.innerHTML = `<table class="res-table"><thead><tr>
            <th>#</th><th>Imovel</th><th>Modalidade</th><th>Periodo</th><th>Noites</th><th>Total</th><th>Status</th>
        </tr></thead><tbody>${data.map(r => `<tr>
            <td>${r.id}</td>
            <td><a href="#/imovel/${r.property_id}" style="color:var(--brand)">#${r.property_id}</a></td>
            <td>${r.modality}</td>
            <td>${r.check_in} &rarr; ${r.check_out}</td>
            <td>${r.nights}</td>
            <td><strong>R$ ${fmt(r.total_price)}</strong></td>
            <td><span class="status-pill ${r.status}">${r.status}</span></td>
        </tr>`).join('')}</tbody></table>`;
    } catch (e) {
        box.innerHTML = `<p class="empty-state">${escapeHtml(e.message)}</p>`;
    }
}

// ---- Init ----
function bindEvents() {
    document.querySelectorAll('.search-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.activeType = tab.dataset.type;
            loadProperties();
        });
    });

    document.querySelectorAll('[data-nav]').forEach(btn => {
        btn.addEventListener('click', () => navigate(btn.dataset.nav));
    });

    $('btn-toggle-advanced').onclick = () => {
        $('advanced-filters').classList.toggle('open');
        $('btn-toggle-advanced').textContent = $('advanced-filters').classList.contains('open') ? 'Ocultar filtros' : 'Filtros avancados';
    };
    $('btn-search').onclick = loadProperties;
    $('btn-refresh').onclick = loadProperties;
    $('btn-clear-filters').onclick = clearFilters;
    $('btn-open-login').onclick = () => $('login-modal').showModal();
    $('login-cancel').onclick = () => $('login-modal').close();
    $('login-submit').onclick = doLogin;
    $('btn-logout').onclick = doLogout;
    $('btn-create-listing').onclick = openCreateListing;
    $('reserve-cancel').onclick = () => $('reserve-modal').close();
    $('reserve-submit').onclick = doReserve;
    $('r-modality').onchange = toggleIncome;

    $('listing-cancel').onclick = () => $('listing-modal').close();
    $('listing-btn-prev').onclick = () => { state.createStep = 1; updateListingSteps(); };
    $('listing-btn-next').onclick = () => { state.createStep = 2; updateListingSteps(); };
    $('listing-btn-save').onclick = saveListing;

    document.querySelectorAll('.modal-close').forEach(b => {
        b.onclick = () => b.closest('dialog')?.close();
    });

    window.addEventListener('hashchange', renderRoute);
}

window.navigate = navigate;
window.openCreateListing = openCreateListing;
window.openEditListing = openEditListing;
window.toggleListingAvailability = toggleListingAvailability;
window.clearFilters = clearFilters;
window.openReserve = openReserve;

document.addEventListener('DOMContentLoaded', () => {
    initRange('f-daily-min', 'f-daily-max', 'f-daily-lbl', state.bounds.daily, 'R$ ');
    initRange('f-monthly-min', 'f-monthly-max', 'f-monthly-lbl', state.bounds.monthly, 'R$ ');
    initRange('f-area-min', 'f-area-max', 'f-area-lbl', state.bounds.area, '');
    bindEvents();
    refreshAuthUI();
    loadProperties();
    renderRoute();
});
