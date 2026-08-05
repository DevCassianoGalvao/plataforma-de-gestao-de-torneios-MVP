(function () {
    'use strict';

    var root = document.body;
    document.querySelectorAll('[data-history-back]').forEach(function (button) { button.addEventListener('click', function () { if (window.history.length > 1) window.history.back(); else window.location.href = '/'; }); });
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
        'whistle': '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/>',
        'mail': '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'archive': '<path d="M3 7h18M5 7v12h14V7M4 4h16l1 3H3l1-3ZM9 11h6"/>',
        'chart': '<path d="M4 19V5M4 19h17M8 16v-5M12 16V8M16 16V4M20 16v-7"/>',
        'scan-line': '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/>',
        'settings-2': '<path d="M20 7h-9M14 17H4M17 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM7 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>',
        'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        'moon': '<path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5 8.5 8.5 0 1 0 20.5 14.5Z"/>',
        'menu': '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x': '<path d="M6 6l12 12M18 6 6 18"/>',
        'chevron-right': '<path d="m9 18 6-6-6-6"/>',
        'circle': '<circle cx="12" cy="12" r="9"/>'
    };
    var navIconMap = {
        overview: 'layout-dashboard', championship: 'trophy', schedule: 'calendar-days', team: 'shield',
        athlete: 'user-round', registration: 'file-check-2', roster: 'clipboard-check', transfer: 'arrow-left-right',
        news: 'newspaper', user: 'users-round', audit: 'scan-line', archive: 'archive', chart: 'chart', profile: 'settings-2', bell: 'bell'
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
    document.querySelectorAll('.knockout-tie').forEach(function (tie) {
        var winnerText = tie.querySelector('small');
        if (!winnerText || winnerText.textContent.indexOf('Classificado:') === -1) return;
        var winner = winnerText.textContent.replace('Classificado:', '').trim();
        tie.querySelectorAll('.bracket-team').forEach(function (team) {
            if (team.textContent.trim() && team.textContent.indexOf(winner) === -1) team.classList.add('is-eliminated');
        });
    });
    document.querySelectorAll('.attention-list a').forEach(function (link) {
        var arrow = link.querySelector('b');
        if (arrow) setIcon(arrow, 'chevron-right', false);
    });
    var statusLabels = {
        draft: 'Rascunho', submitted: 'Enviada', under_review: 'Em análise', pending_correction: 'Pendente',
        approved: 'Aprovada', rejected: 'Rejeitada', suspended: 'Suspensa', cancelled: 'Cancelada',
        active: 'Ativo', inactive: 'Inativo', blocked: 'Bloqueado', transferred: 'Transferido', archived: 'Arquivado',
        scheduled: 'Agendada', confirmed: 'Confirmada', postponed: 'Adiada', finished: 'Encerrada', homologated: 'Aprovada',
        published: 'Publicada', unpublished: 'Despublicada', pending: 'Pendente', replaced: 'Substituído', expired: 'Expirado',
        configured: 'Configurada', in_progress: 'Em andamento', wo: 'W.O.', withdrawn: 'Retirada', homologated: 'Aprovada', public: 'Pública', private: 'Privada', accountability: 'Prestação de contas', available: 'Disponível'
    };
    document.querySelectorAll('.status, select option').forEach(function (element) {
        var key = element.classList.contains('status') ? element.textContent.trim() : element.value;
        if (statusLabels[key]) element.textContent = statusLabels[key];
    });
    var technicalLabels = Object.assign({}, statusLabels, { visibility: 'Visibilidade', status: 'Situação', in_review: 'Em análise', organizer: 'Organização', communication: 'Comunicação', homologated: 'Aprovada', homologado: 'aprovado', homologada: 'aprovada', homologados: 'aprovados', homologadas: 'aprovadas' });
    var textWalker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var textNode;
    while ((textNode = textWalker.nextNode())) {
        if (textNode.parentElement && ['SCRIPT', 'STYLE', 'OPTION'].indexOf(textNode.parentElement.tagName) !== -1) continue;
        Object.keys(technicalLabels).forEach(function (key) {
            textNode.nodeValue = textNode.nodeValue.replace(new RegExp('(^|[^A-Za-z_])' + key + '($|[^A-Za-z_])', 'g'), '$1' + technicalLabels[key] + '$2');
        });
        textNode.nodeValue = textNode.nodeValue.replace(/A forma.{0,20}o fica registrada por jogo\. Titulares e reservas s.{0,12} aparecem ap.{0,18} confirma.{0,20}o t.{0,8}cnica\.?/i, 'Confira a formação escolhida para esta partida e os atletas relacionados.');
        textNode.nodeValue = textNode.nodeValue.replace(/Movimenta.{0,8}o publicada de demonstra.{0,8}o\.?/i, 'Movimentação registrada no campeonato.');
    }

    var mojibakeMap = { 'Ã¡': 'á', 'Ã£': 'ã', 'Ã§': 'ç', 'Ã©': 'é', 'Ãª': 'ê', 'Ã­': 'í', 'Ã³': 'ó', 'Ã´': 'ô', 'Ãµ': 'õ', 'Ãº': 'ú', 'Ã‰': 'É', 'Â·': '·', 'Â©': '©', 'Â': '' };
    var plainLabels = { Prestacao_de_contas: 'Prestação de contas', Prestacao: 'Prestação', prestacao: 'prestação', Notificacoes: 'Notificações', notificacoes: 'notificações', Atualizacao: 'Atualização', atualizacao: 'atualização', Organizacao: 'Organização', organizacao: 'organização', Inscricoes: 'Inscrições', inscricoes: 'inscrições', Classificacao: 'Classificação', classificacao: 'classificação', Informacoes: 'Informações', informacoes: 'informações', Configuracao: 'Configuração', configuracao: 'configuração', Publica: 'Pública', publica: 'pública', Sessao: 'Sessão', sessao: 'sessão', Administracao: 'Administração', administracao: 'administração', Comissao: 'Comissão', comissao: 'comissão', Responsaveis: 'Responsáveis', responsaveis: 'responsáveis', Operacao: 'Operação', operacao: 'operação', Situacao: 'Situação', situacao: 'situação', Atualizacoes: 'Atualizações', atualizacoes: 'atualizações' };
    var normalizeMojibake = function (value) {
        var normalized = Object.keys(mojibakeMap).reduce(function (result, key) { return result.split(key).join(mojibakeMap[key]); }, value);
        Object.keys(plainLabels).sort(function (a, b) { return b.length - a.length; }).forEach(function (key) { normalized = normalized.replace(new RegExp(key.replace(/_/g, '\\s+'), 'g'), plainLabels[key]); });
        return normalized;
    };
    document.querySelectorAll('body *').forEach(function (element) {
        if (['SCRIPT', 'STYLE'].indexOf(element.tagName) !== -1) return;
        Array.prototype.forEach.call(element.childNodes, function (node) {
            if (node.nodeType === Node.TEXT_NODE) node.nodeValue = normalizeMojibake(node.nodeValue);
        });
        ['placeholder', 'aria-label', 'title'].forEach(function (attribute) { if (element.hasAttribute(attribute)) element.setAttribute(attribute, normalizeMojibake(element.getAttribute(attribute))); });
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
            setIcon(toggle, 'menu', false);
            if (returnFocus) toggle.focus();
        };
        var open = function () {
            drawer.classList.add('is-open');
            root.classList.add(bodyClass);
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', closeLabel);
            toggle.setAttribute('title', closeLabel);
            setIcon(toggle, 'x', false);
            var firstLink = drawer.querySelector('a');
            if (firstLink) firstLink.focus();
        };
        toggle.addEventListener('click', function () { drawer.classList.contains('is-open') ? close(false) : open(); });
        dismissers.forEach(function (button) { button.addEventListener('click', function () { close(true); }); });
        drawer.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', function () { close(false); }); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(true); });
        window.matchMedia('(min-width: 801px)').addEventListener('change', function (event) { if (event.matches) close(false); });
    }

    function setupSidebarAccordions() {
        var groups = Array.prototype.slice.call(document.querySelectorAll('[data-sidebar-group]'));
        if (!groups.length) return;

        var storageKey = 'torneio.admin.sidebar.groups';
        var stored = {};
        try { stored = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}') || {}; } catch (error) { stored = {}; }

        var mobileQuery = window.matchMedia('(max-width: 800px)');
        var apply = function (group, open) {
            var toggle = group.querySelector('[data-sidebar-group-toggle]');
            var items = group.querySelector('[data-sidebar-group-items]');
            if (!toggle || !items) return;
            group.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            items.hidden = !open;
            var chevron = toggle.querySelector('.sidebar-chevron');
            if (chevron) chevron.classList.toggle('is-rotated', open);
        };
        var setOpen = function (group, open, persist) {
            if (open && mobileQuery.matches) {
                groups.forEach(function (other) { if (other !== group) apply(other, false); });
            }
            apply(group, open);
            if (persist) {
                stored[group.dataset.sidebarGroup] = open;
                try { window.sessionStorage.setItem(storageKey, JSON.stringify(stored)); } catch (error) { /* storage is optional */ }
            }
        };

        var activeGroup = groups.filter(function (group) { return group.dataset.active === 'true'; })[0] || null;
        var firstOpen = false;
        groups.forEach(function (group) {
            var key = group.dataset.sidebarGroup;
            var shouldOpen = group.dataset.active === 'true' || stored[key] === true;
            if (mobileQuery.matches && activeGroup) shouldOpen = group === activeGroup;
            if (mobileQuery.matches && shouldOpen && firstOpen) shouldOpen = false;
            if (shouldOpen) firstOpen = true;
            apply(group, shouldOpen);
            var toggle = group.querySelector('[data-sidebar-group-toggle]');
            if (toggle) toggle.addEventListener('click', function () { setOpen(group, !group.classList.contains('is-open'), true); });
        });

        var handleViewport = function () {
            if (!mobileQuery.matches) return;
            var openFound = false;
            groups.forEach(function (group) {
                if (group.classList.contains('is-open') && !openFound) { openFound = true; return; }
                if (group.classList.contains('is-open')) apply(group, false);
            });
        };
        handleViewport();
        if (mobileQuery.addEventListener) mobileQuery.addEventListener('change', handleViewport);
        else mobileQuery.addListener(handleViewport);
    }

    var sidebar = document.querySelector('[data-sidebar]');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    setupSidebarAccordions();
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
        var slides = document.querySelectorAll('.portal-feature-slide');
        if (slides.length <= 1) { var onlyDot = document.querySelector('.portal-feature-dots'); if (onlyDot) onlyDot.hidden = true; }
        if (slides.length > 1) {
            var slideIndex = 0;
            var dots = document.querySelectorAll('.portal-feature-dots button');
            var showSlide = function (index) { slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === index); }); dots.forEach(function (dot, i) { dot.classList.toggle('is-active', i === index); }); };
            dots.forEach(function (dot, i) { dot.addEventListener('click', function () { slideIndex = i; showSlide(slideIndex); }); });
            showSlide(slideIndex);
            window.setInterval(function () { slideIndex = (slideIndex + 1) % slides.length; showSlide(slideIndex); }, 6000);
        }
        if (portalNav) {
            portalNav.querySelectorAll('a').forEach(function (link) {
                var linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
                if (linkPath === portalPath) link.setAttribute('aria-current', 'page');
            });
        }

        var simulator = document.querySelector('[data-standings-simulator]');
        if (simulator) {
            var simulatorEyebrow = simulator.querySelector('.eyebrow');
            var simulatorHeading = simulator.querySelector('h2');
            var simulatorCopy = simulator.querySelector('.section-heading p:last-child');
            if (simulatorEyebrow) simulatorEyebrow.textContent = 'Simulador de resultados';
            if (simulatorHeading) simulatorHeading.textContent = 'Projete a classifica\u00e7\u00e3o';
            if (simulatorCopy) simulatorCopy.textContent = 'Simule livremente qualquer partida da fase de grupos sem alterar os dados oficiais.';
            var fixtures = [];
            try { fixtures = JSON.parse(simulator.dataset.fixtures || '[]'); } catch (error) { fixtures = []; }
            var endpoint = simulator.dataset.simulatorEndpoint || window.location.pathname.replace(/\/+$/, '') + '/simular';
            var status = simulator.querySelector('[data-simulator-status]');
            var label = simulator.querySelector('[data-simulator-label]');
            if (!status) {
                status = document.createElement('span');
                status.className = 'simulator-status-text';
                status.setAttribute('aria-live', 'polite');
                status.textContent = 'Informe os dois placares para ver a proje\u00e7\u00e3o.';
                simulator.querySelector('.section-heading').appendChild(status);
            }
            if (!label) {
                label = document.createElement('span');
                label.className = 'simulator-result-label';
                label.textContent = 'Dados oficiais';
                simulator.querySelector('.section-heading').appendChild(label);
            }
            var timer = null;
            var requestNumber = 0;
            var inputsByMatch = function () {
                var values = {};
                simulator.querySelectorAll('input[data-simulator-score]').forEach(function (input) {
                    var id = input.getAttribute('data-match');
                    values[id] = values[id] || {};
                    values[id][input.getAttribute('data-simulator-score')] = input.value;
                });
                return values;
            };
            var writeRows = function (groups) {
                groups.forEach(function (group) {
                    var rows = group.simulated || [];
                    rows.forEach(function (item) {
                        var row = document.querySelector('[data-standings-team="' + String(item.team_id) + '"]');
                        if (!row) return;
                        var cells = row.querySelectorAll('td');
                        if (cells[0]) cells[0].textContent = String(item.position);
                        [item.matches_played, item.wins, item.draws, item.losses, item.goals_for, item.goals_against, item.goal_difference].forEach(function (value, offset) { if (cells[offset + 2]) cells[offset + 2].textContent = String(value); });
                        if (cells[9]) cells[9].innerHTML = '<strong>' + String(item.points) + '</strong>';
                        row.classList.toggle('is-simulator-changed', Number(item.position_change || 0) !== 0);
                        row.dataset.simulatorPositionChange = String(item.position_change || 0);
                        var tbody = row.parentNode;
                        if (tbody) tbody.appendChild(row);
                    });
                });
            };
            var requestProjection = function () {
                var currentRequest = ++requestNumber;
                var params = new URLSearchParams();
                var values = inputsByMatch();
                Object.keys(values).forEach(function (id) {
                    var score = values[id];
                    if (score.home === '' || score.away === '' || score.home === undefined || score.away === undefined) return;
                    params.append('scores[' + id + '][home]', score.home);
                    params.append('scores[' + id + '][away]', score.away);
                });
                if (label) label.textContent = 'Calculando...';
                if (status) status.textContent = 'Atualizando a proje\u00e7\u00e3o conforme o regulamento publicado.';
                fetch(endpoint, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' }, body: params })
                    .then(function (response) { return response.json().then(function (payload) { return { response: response, payload: payload }; }); })
                    .then(function (result) {
                        if (currentRequest !== requestNumber) return;
                        if (!result.response.ok || !result.payload.ok) throw new Error((result.payload.errors || ['Nao foi possivel calcular a projecao.'])[0]);
                        writeRows(result.payload.groups || []);
                        if (label) label.textContent = result.payload.changed ? 'Projecao simulada' : 'Dados oficiais';
                        if (status) status.textContent = result.payload.changed ? 'A tabela exibida \u00e9 uma simula\u00e7\u00e3o local. A classifica\u00e7\u00e3o oficial permanece intacta.' : 'Informe os dois placares para ver a proje\u00e7\u00e3o.';
                    })
                    .catch(function (error) { if (currentRequest !== requestNumber) return; if (label) label.textContent = 'Dados oficiais'; if (status) status.textContent = error.message || 'Nao foi possivel atualizar a projecao.'; });
            };
            var scheduleProjection = function () { window.clearTimeout(timer); timer = window.setTimeout(requestProjection, 180); };
            simulator.addEventListener('input', scheduleProjection);
            var reset = simulator.querySelector('[data-simulator-reset]');
            if (reset) reset.addEventListener('click', function () { simulator.querySelectorAll('input[data-simulator-score]').forEach(function (input) { input.value = input.getAttribute('data-official-score') || ''; }); requestProjection(); });
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

    document.querySelectorAll('[data-file-input]').forEach(function (input) {
        var state = document.querySelector('[data-file-state][data-file-input-id="' + input.id + '"]');
        if (!state) return;
        var renderFile = function () {
            state.textContent = input.files && input.files.length ? input.files[0].name : state.dataset.emptyLabel;
        };
        input.addEventListener('change', renderFile);
        renderFile();
    });

    var portalBody = document.querySelector('[data-portal-primary]');
    if (portalBody) {
        ['primary', 'secondary', 'accent'].forEach(function (key) {
            var value = portalBody.dataset['portal' + key.charAt(0).toUpperCase() + key.slice(1)];
            if (value && /^#[0-9a-f]{6}$/i.test(value)) portalBody.style.setProperty('--portal-' + key, value);
        });
    }
}());
