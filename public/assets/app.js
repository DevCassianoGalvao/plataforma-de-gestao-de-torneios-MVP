(function () {
    'use strict';

    var root = document.body;
    var iconPaths = {
        'layout-dashboard': '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="4" rx="1"/><rect x="14" y="10" width="7" height="11" rx="1"/><rect x="3" y="13" width="7" height="8" rx="1"/>',
        'trophy': '<path d="M8 21h8M12 17v4M7 4h10M5 4v3a7 7 0 0 0 14 0V4M5 4H3v2a4 4 0 0 0 4 4M19 4h2v2a4 4 0 0 1-4 4M7 4V2h10v2"/>',
        'calendar-days': '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>',
        'shield': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>',
        'shield-alert': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="M12 8v4M12 16h.01"/>',
        'user-round': '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users-round': '<path d="M18 21a6 6 0 0 0-12 0M15 3.5a4 4 0 0 1 0 7.5M21 21a6 6 0 0 0-3.5-5.5M9 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>',
        'file-check-2': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 15l2 2 4-4"/>',
        'clipboard-check': '<rect x="4" y="4" width="16" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 13l2 2 4-4"/>',
        'arrow-left-right': '<path d="M8 3 4 7l4 4M4 7h16M16 21l4-4-4-4M20 17H4"/>',
        'newspaper': '<path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/>',
        'bell': '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'scan-line': '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/>',
        'settings-2': '<path d="M20 7h-9M14 17H4M17 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM7 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>',
        'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        'moon': '<path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5 8.5 8.5 0 1 0 20.5 14.5Z"/>',
        'menu': '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'chevron-right': '<path d="m9 18 6-6-6-6"/>',
        'circle': '<circle cx="12" cy="12" r="9"/>'
    };
    var navIconMap = {
        overview: 'layout-dashboard', championship: 'trophy', schedule: 'calendar-days', team: 'shield',
        athlete: 'user-round', registration: 'file-check-2', roster: 'clipboard-check', transfer: 'arrow-left-right',
        news: 'newspaper', user: 'users-round', audit: 'scan-line', profile: 'settings-2', bell: 'bell'
    };
    function createIcon(name) {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.8');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.classList.add('ui-icon');
        svg.innerHTML = iconPaths[name] || iconPaths['circle'];
        return svg;
    }
    function setIcon(host, name, keepLabel) {
        if (!host || !iconPaths[name]) return;
        var label = keepLabel ? host.textContent.trim() : '';
        host.textContent = '';
        host.appendChild(createIcon(name));
        if (label) {
            var text = document.createElement('span');
            text.textContent = label;
            host.appendChild(text);
        }
    }
    var preferred = 'dark';
    root.dataset.theme = 'dark';

    document.querySelectorAll('[data-icon]').forEach(function (element) {
        var name = navIconMap[element.dataset.icon] || element.dataset.icon;
        setIcon(element, name, element.matches('.button'));
    });
    document.querySelectorAll('.attention-list a').forEach(function (link) {
        var arrow = link.querySelector('b');
        if (arrow) setIcon(arrow, 'chevron-right', false);
    });
    var statusLabels = {
        draft: 'Rascunho', submitted: 'Enviada', under_review: 'Em análise', pending_correction: 'Pendente',
        approved: 'Aprovada', rejected: 'Rejeitada', suspended: 'Suspensa', cancelled: 'Cancelada',
        active: 'Ativo', inactive: 'Inativo', blocked: 'Bloqueado', transferred: 'Transferido', archived: 'Arquivado',
        scheduled: 'Agendada', confirmed: 'Confirmada', postponed: 'Adiada', finished: 'Encerrada', homologated: 'Homologada',
        published: 'Publicada', unpublished: 'Despublicada', pending: 'Pendente', replaced: 'Substituído', expired: 'Expirado',
        configured: 'Configurada', in_progress: 'Em andamento', wo: 'W.O.', withdrawn: 'Retirada'
    };
    document.querySelectorAll('.status, select option').forEach(function (element) {
        var key = element.classList.contains('status') ? element.textContent.trim() : element.value;
        if (statusLabels[key]) element.textContent = statusLabels[key];
    });

    function setupMobileNavigation(drawer, toggle, dismissSelector, bodyClass, openLabel, closeLabel) {
        if (!drawer || !toggle) return;
        setIcon(toggle, 'menu', false);
        var dismissers = document.querySelectorAll(dismissSelector);
        var close = function (returnFocus) {
            if (!drawer.classList.contains('is-open')) return;
            drawer.classList.remove('is-open');
            root.classList.remove(bodyClass);
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', openLabel);
            toggle.setAttribute('title', openLabel);
            if (returnFocus) toggle.focus();
        };
        var open = function () {
            drawer.classList.add('is-open');
            root.classList.add(bodyClass);
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', closeLabel);
            toggle.setAttribute('title', closeLabel);
            var firstLink = drawer.querySelector('a');
            if (firstLink) firstLink.focus();
        };
        toggle.addEventListener('click', function () { drawer.classList.contains('is-open') ? close(false) : open(); });
        dismissers.forEach(function (button) { button.addEventListener('click', function () { close(true); }); });
        drawer.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', function () { close(false); }); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(true); });
        window.matchMedia('(min-width: 801px)').addEventListener('change', function (event) { if (event.matches) close(false); });
    }

    var sidebar = document.querySelector('[data-sidebar]');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    setupMobileNavigation(sidebar, sidebarToggle, '[data-sidebar-dismiss]', 'has-mobile-menu', 'Abrir menu', 'Fechar menu');

    var portalNav = document.querySelector('[data-portal-nav]');
    var portalNavToggle = document.querySelector('[data-portal-nav-toggle]');
    setupMobileNavigation(portalNav, portalNavToggle, '[data-portal-nav-dismiss]', 'has-portal-menu', 'Abrir navegação', 'Fechar navegação');

    if (root.classList.contains('public-portal')) {
        var portalPath = window.location.pathname.replace(/\/+$/, '') || '/';
        var portalParts = portalPath.split('/');
        var portalLastPart = portalParts[portalParts.length - 1];
        var portalPages = {
            'proximos-jogos': 'next', resultados: 'results', classificacao: 'standings', grupos: 'groups',
            'mata-mata': 'knockout', equipes: 'teams', atletas: 'athletes', artilharia: 'goals',
            assistencias: 'assists', cartoes: 'cards', regulamento: 'regulation', campeao: 'champion',
            noticias: 'news', 'vai-e-vem': 'transfers', arbitragem: 'officials', contato: 'contact'
        };
        var portalPage = portalPages[portalLastPart] || 'home';
        if (portalParts.indexOf('partidas') !== -1) portalPage = 'match';
        if (portalParts.indexOf('noticias') !== -1) portalPage = 'news';
        if (portalParts.indexOf('vai-e-vem') !== -1) portalPage = 'transfers';
        if (portalParts.indexOf('equipes') !== -1 && !portalPages[portalLastPart]) portalPage = 'team';
        if (portalParts.indexOf('atletas') !== -1 && !portalPages[portalLastPart]) portalPage = 'athlete';
        root.classList.add('portal-page--' + portalPage);
        if (portalNav) {
            portalNav.querySelectorAll('a').forEach(function (link) {
                var linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
                if (linkPath === portalPath) link.setAttribute('aria-current', 'page');
            });
        }

        var simulator = document.querySelector('[data-standings-simulator]');
        if (simulator) {
            var fixtures = [];
            var points = { win: 3, draw: 1, loss: 0 };
            try { fixtures = JSON.parse(simulator.dataset.fixtures || '[]'); points = JSON.parse(simulator.dataset.points || '{}'); } catch (error) { fixtures = []; }
            var rows = {};
            document.querySelectorAll('[data-standings-team]').forEach(function (row) {
                var cells = row.querySelectorAll('td');
                var number = function (index) { return parseInt((cells[index] && cells[index].textContent) || '0', 10) || 0; };
                rows[row.dataset.standingsTeam] = { element: row, group: row.dataset.standingsGroup, base: { matches: number(2), wins: number(3), draws: number(4), losses: number(5), goalsFor: number(6), goalsAgainst: number(7), difference: number(8), points: number(9) } };
            });
            var writeRow = function (item, values, position) {
                var cells = item.element.querySelectorAll('td');
                if (cells[0]) cells[0].textContent = String(position);
                [values.matches, values.wins, values.draws, values.losses, values.goalsFor, values.goalsAgainst, values.goalsFor - values.goalsAgainst].forEach(function (value, offset) { if (cells[offset + 2]) cells[offset + 2].textContent = String(value); });
                if (cells[9]) cells[9].innerHTML = '<strong>' + String(values.points) + '</strong>';
            };
            var calculate = function () {
                var current = {};
                Object.keys(rows).forEach(function (key) { current[key] = Object.assign({}, rows[key].base); });
                fixtures.forEach(function (fixture) {
                    var home = simulator.querySelector('[data-simulator-score="home"][data-match="' + fixture.id + '"]');
                    var away = simulator.querySelector('[data-simulator-score="away"][data-match="' + fixture.id + '"]');
                    if (!home || !away || home.value === '' || away.value === '') return;
                    var homeScore = parseInt(home.value, 10); var awayScore = parseInt(away.value, 10);
                    if (homeScore < 0 || awayScore < 0 || !Number.isFinite(homeScore) || !Number.isFinite(awayScore) || !current[fixture.home_team_id] || !current[fixture.away_team_id]) return;
                    var homeStats = current[fixture.home_team_id]; var awayStats = current[fixture.away_team_id];
                    homeStats.matches++; awayStats.matches++; homeStats.goalsFor += homeScore; homeStats.goalsAgainst += awayScore; awayStats.goalsFor += awayScore; awayStats.goalsAgainst += homeScore;
                    if (homeScore > awayScore) { homeStats.wins++; awayStats.losses++; homeStats.points += Number(points.win || 3); awayStats.points += Number(points.loss || 0); }
                    else if (homeScore < awayScore) { awayStats.wins++; homeStats.losses++; awayStats.points += Number(points.win || 3); homeStats.points += Number(points.loss || 0); }
                    else { homeStats.draws++; awayStats.draws++; homeStats.points += Number(points.draw || 1); awayStats.points += Number(points.draw || 1); }
                });
                var byGroup = {};
                Object.keys(rows).forEach(function (key) { var row = rows[key]; (byGroup[row.group] = byGroup[row.group] || []).push({ key: key, row: row, values: current[key] }); });
                Object.keys(byGroup).forEach(function (group) { byGroup[group].sort(function (a, b) { return b.values.points - a.values.points || (b.values.goalsFor - b.values.goalsAgainst) - (a.values.goalsFor - a.values.goalsAgainst) || b.values.goalsFor - a.values.goalsFor || a.row.element.textContent.localeCompare(b.row.element.textContent); }).forEach(function (item, index) { writeRow(item.row, item.values, index + 1); item.row.element.parentNode.appendChild(item.row.element); }); });
            };
            simulator.addEventListener('input', calculate);
            var reset = simulator.querySelector('[data-simulator-reset]');
            if (reset) reset.addEventListener('click', function () { simulator.querySelectorAll('input[data-simulator-score]').forEach(function (input) { input.value = ''; }); calculate(); });
        }
    }

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('aria-controls'));
            if (!input) return;
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-label', visible ? 'Mostrar senha' : 'Ocultar senha');
            button.setAttribute('title', button.getAttribute('aria-label'));
        });
    });

    document.querySelectorAll('[data-color-field]').forEach(function (field) {
        var picker = field.querySelector('input[type="color"]');
        var code = field.querySelector('[data-color-code]');
        if (!picker || !code) return;
        var renderColor = function () { code.textContent = picker.value.toUpperCase(); };
        picker.addEventListener('input', renderColor);
        picker.addEventListener('change', renderColor);
        renderColor();
    });

    var portalBody = document.querySelector('[data-portal-primary]');
    if (portalBody) {
        ['primary', 'secondary', 'accent'].forEach(function (key) {
            var value = portalBody.dataset['portal' + key.charAt(0).toUpperCase() + key.slice(1)];
            if (value && /^#[0-9a-f]{6}$/i.test(value)) portalBody.style.setProperty('--portal-' + key, value);
        });
    }
}());
