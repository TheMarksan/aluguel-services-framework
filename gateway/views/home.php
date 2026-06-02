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
        <a class="logo" href="#/">
            <span class="logo-mark" aria-hidden="true">A</span>
            <span>Aluguel Hub<small>Framework Whitelabel &middot; UFAL</small></span>
        </a>
        <nav class="app-nav" aria-label="Principal">
            <button type="button" data-nav="explore" class="active">Explorar</button>
            <button type="button" data-nav="listings">Meus an&uacute;ncios</button>
            <button type="button" data-nav="reservations">Reservas</button>
        </nav>
        <div class="nav-actions">
            <button type="button" id="btn-create-listing" class="btn btn-accent btn-sm hidden">+ Anunciar</button>
            <span id="user-label" class="user-chip hidden"></span>
            <button type="button" id="btn-open-login" class="btn btn-primary btn-sm">Entrar</button>
            <button type="button" id="btn-logout" class="btn btn-ghost btn-sm hidden">Sair</button>
        </div>
    </header>

    <!-- Hero + busca (somente na view Explorar) -->
    <section class="hero" id="hero-section">
        <div class="hero-inner">
            <div class="hero-kicker">Casas, apartamentos e reservas em um so fluxo</div>
            <h1>Encontre o im&oacute;vel certo com uma busca mais esperta</h1>
            <p>Compare pre&ccedil;os, &aacute;rea, quartos e disponibilidade em uma interface pronta para white-label.</p>

            <div class="search-panel">
                <div class="search-tabs" role="tablist">
                    <button type="button" class="search-tab active" data-type="">Todos</button>
                    <button type="button" class="search-tab" data-type="apartamento">Apartamentos</button>
                    <button type="button" class="search-tab" data-type="casa">Casas</button>
                </div>

                <div class="search-row">
                    <div class="search-field">
                        <label for="f-city">Localiza&ccedil;&atilde;o</label>
                        <input id="f-city" type="text" placeholder="Ex.: Macei&oacute;">
                    </div>
                    <div class="search-field">
                        <label for="f-q">Palavra-chave</label>
                        <input id="f-q" type="text" placeholder="T&iacute;tulo, endere&ccedil;o ou descri&ccedil;&atilde;o">
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
                            <option value="area_desc">Maior &aacute;rea</option>
                            <option value="bedrooms_desc">Mais quartos</option>
                        </select>
                    </div>
                </div>

                <div class="advanced-filters" id="advanced-filters">
                    <div class="filter-grid-2">
                        <div class="range-field">
                            <label>Di&aacute;ria (R$)</label>
                            <div class="range-values" id="f-daily-lbl">R$ 0,00 — R$ 10.000,00</div>
                            <div class="range-number-row">
                                <input type="number" id="f-daily-min-num" min="0" placeholder="Min.">
                                <input type="number" id="f-daily-max-num" min="0" placeholder="Max.">
                            </div>
                            <div class="range-dual">
                                <input type="range" id="f-daily-min" value="0">
                                <input type="range" id="f-daily-max" value="10000">
                            </div>
                        </div>
                        <div class="range-field">
                            <label>Mensal (R$)</label>
                            <div class="range-values" id="f-monthly-lbl">R$ 0,00 — R$ 50.000,00</div>
                            <div class="range-number-row">
                                <input type="number" id="f-monthly-min-num" min="0" placeholder="Min.">
                                <input type="number" id="f-monthly-max-num" min="0" placeholder="Max.">
                            </div>
                            <div class="range-dual">
                                <input type="range" id="f-monthly-min" value="0">
                                <input type="range" id="f-monthly-max" value="50000">
                            </div>
                        </div>
                        <div class="range-field">
                            <label>&Aacute;rea (m&sup2;)</label>
                            <div class="range-values" id="f-area-lbl">0 — 500</div>
                            <div class="range-number-row">
                                <input type="number" id="f-area-min-num" min="0" placeholder="Min.">
                                <input type="number" id="f-area-max-num" min="0" placeholder="Max.">
                            </div>
                            <div class="range-dual">
                                <input type="range" id="f-area-min" value="0">
                                <input type="range" id="f-area-max" value="500">
                            </div>
                        </div>
                        <div class="search-row" style="align-items:end">
                            <div class="search-field">
                                <label for="f-bedrooms-min">Quartos (min)</label>
                                <select id="f-bedrooms-min">
                                    <option value="0">Qualquer</option>
                                    <option value="1">1+</option><option value="2">2+</option>
                                    <option value="3">3+</option><option value="4">4+</option>
                                </select>
                            </div>
                            <div class="search-field">
                                <label for="f-bedrooms-max">Quartos (m&aacute;x)</label>
                                <select id="f-bedrooms-max">
                                    <option value="10">Qualquer</option>
                                    <option value="1">1</option><option value="2">2</option>
                                    <option value="3">3</option><option value="4">4</option><option value="5">5+</option>
                                </select>
                            </div>
                            <div class="search-field">
                                <label for="f-bathrooms-min">Banheiros (m&iacute;n)</label>
                                <select id="f-bathrooms-min">
                                    <option value="0">Qualquer</option>
                                    <option value="1">1+</option><option value="2">2+</option><option value="3">3+</option>
                                </select>
                            </div>
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

    <div class="results-bar" id="results-bar">
        <div class="results-meta">
            <div class="results-count" id="results-count">Carregando&hellip;</div>
            <button type="button" id="btn-refresh" class="btn btn-ghost btn-sm">Atualizar</button>
        </div>
    </div>

    <main>
        <!-- VIEW: Explorar -->
        <section id="view-explore" class="app-view active">
            <div id="properties" class="property-grid">
                <p class="empty-state">Carregando cat&aacute;logo&hellip;</p>
            </div>
        </section>

        <!-- VIEW: Detalhe do imovel -->
        <section id="view-detail" class="app-view">
            <div id="detail-content"></div>
        </section>

        <!-- VIEW: Meus anuncios -->
        <section id="view-listings" class="app-view">
            <div class="section-head">
                <h2>Meus an&uacute;ncios</h2>
                <button type="button" class="btn btn-primary btn-sm" onclick="openCreateListing()">+ Novo an&uacute;ncio</button>
            </div>
            <div class="listing-stats" id="listings-stats"></div>
            <div class="reservations-wrap" id="listings-content">
                <p class="empty-state">Carregando an&uacute;ncios&hellip;</p>
            </div>
        </section>

        <!-- VIEW: Reservas -->
        <section id="view-reservations" class="app-view">
            <div class="section-head">
                <h2>Minhas reservas</h2>
            </div>
            <div class="reservations-wrap" id="reservations">
                <p class="empty-state">Nenhuma reserva carregada.</p>
            </div>
        </section>
    </main>

    <!-- Modal: Login -->
    <dialog id="login-modal">
        <div class="modal-head">
            <h2>Bem-vindo de volta</h2>
            <button type="button" class="modal-close" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size:.88rem;color:var(--muted);margin:0 0 1rem">Entre para reservar, anunciar e acompanhar seus im&oacute;veis.</p>
            <div class="form-field"><label for="login-email">E-mail</label><input id="login-email" type="email" value="admin@aluguel.dev" autocomplete="username"></div>
            <div class="form-field"><label for="login-pass">Senha</label><input id="login-pass" type="password" value="admin123" autocomplete="current-password"></div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="login-cancel">Cancelar</button>
                <button type="button" class="btn btn-primary" id="login-submit">Entrar</button>
            </div>
        </div>
    </dialog>

    <!-- Modal: Reserva -->
    <dialog id="reserve-modal" class="modal-lg">
        <div class="modal-head">
            <h2>Confirmar reserva</h2>
            <button type="button" class="modal-close" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-body">
            <p id="reserve-property" style="font-weight:600;color:var(--navy);margin:0 0 .5rem"></p>
            <div id="reserve-summary"></div>
            <div class="form-field">
                <label for="r-modality">Modalidade</label>
                <select id="r-modality">
                    <option value="vacation">Temporada</option>
                    <option value="long_term">Longa dura&ccedil;&atilde;o</option>
                </select>
            </div>
            <div class="form-grid">
                <div class="form-field"><label for="r-checkin">Check-in</label><input id="r-checkin" type="date"></div>
                <div class="form-field"><label for="r-checkout">Check-out</label><input id="r-checkout" type="date"></div>
            </div>
            <div id="r-income-wrap" class="form-field hidden">
                <label for="r-income">Renda mensal (R$) &mdash; exigida na longa dura&ccedil;&atilde;o</label>
                <input id="r-income" type="number" min="0" placeholder="Ex.: 13000">
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="reserve-cancel">Cancelar</button>
                <button type="button" class="btn btn-accent" id="reserve-submit">Confirmar reserva</button>
            </div>
        </div>
    </dialog>

    <!-- Modal: Criar / editar anuncio -->
    <dialog id="listing-modal" class="modal-xl">
        <div class="modal-head">
            <h2 id="listing-modal-title">Criar an&uacute;ncio</h2>
            <button type="button" class="modal-close" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-steps">
            <div class="modal-step active" data-step="1">1. Im&oacute;vel</div>
            <div class="modal-step" data-step="2">2. Pre&ccedil;os e publica&ccedil;&atilde;o</div>
        </div>
        <div class="modal-body">
            <div id="listing-step-1">
                <div class="form-grid">
                    <div class="form-field full"><label for="l-title">T&iacute;tulo do an&uacute;ncio</label><input id="l-title" placeholder="Ex.: Apartamento Beira-Mar"></div>
                    <div class="form-field full"><label for="l-desc">Descri&ccedil;&atilde;o</label><textarea id="l-desc" placeholder="Descreva o im&oacute;vel..."></textarea></div>
                    <div class="form-field"><label for="l-type">Tipo</label>
                        <select id="l-type"><option value="apartamento">Apartamento</option><option value="casa">Casa</option></select>
                    </div>
                    <div class="form-field"><label for="l-city">Cidade</label><input id="l-city" placeholder="Macei&oacute;"></div>
                    <div class="form-field full"><label for="l-address">Endere&ccedil;o</label><input id="l-address" placeholder="Rua, n&uacute;mero"></div>
                    <div class="form-field"><label for="l-bedrooms">Quartos</label><input id="l-bedrooms" type="number" min="1" value="2"></div>
                    <div class="form-field"><label for="l-bathrooms">Banheiros</label><input id="l-bathrooms" type="number" min="1" value="1"></div>
                    <div class="form-field"><label for="l-area">&Aacute;rea (m&sup2;)</label><input id="l-area" type="number" min="0" step="0.01"></div>
                </div>
            </div>
            <div id="listing-step-2" class="hidden">
                <div class="form-grid">
                    <div class="form-field"><label for="l-daily">Di&aacute;ria (R$)</label><input id="l-daily" type="number" min="0" step="0.01"></div>
                    <div class="form-field"><label for="l-monthly">Mensal (R$)</label><input id="l-monthly" type="number" min="0" step="0.01"></div>
                    <div class="form-field"><label for="l-available">Status</label>
                        <select id="l-available"><option value="1">Dispon&iacute;vel (ativo)</option><option value="0">Indispon&iacute;vel (pausado)</option></select>
                    </div>
                </div>
                <p style="font-size:.82rem;color:var(--muted);margin-top:.5rem">O an&uacute;ncio ser&aacute; vinculado ao seu usu&aacute;rio logado como propriet&aacute;rio.</p>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="listing-cancel">Cancelar</button>
                <button type="button" class="btn btn-ghost hidden" id="listing-btn-prev">Voltar</button>
                <button type="button" class="btn btn-primary hidden" id="listing-btn-next">Pr&oacute;ximo</button>
                <button type="button" class="btn btn-accent hidden" id="listing-btn-save">Publicar an&uacute;ncio</button>
            </div>
        </div>
    </dialog>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>
    <script src="/assets/js/app.js"></script>
</body>
</html>
