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
        news: 'newspaper', user: 'users-round', audit: 'scan-line', profile: 'settings-2'
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
    var savedTheme = null;
    try { savedTheme = window.localStorage.getItem('torneios-theme'); } catch (error) { savedTheme = null; }
    var preferred = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    if (root.classList.contains('public-portal') && !savedTheme) { preferred = 'light'; }
    root.dataset.theme = preferred;

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
        configured: 'Configurada', in_progress: 'Em andamento'
    };
    document.querySelectorAll('.status, select option').forEach(function (element) {
        var key = element.classList.contains('status') ? element.textContent.trim() : element.value;
        if (statusLabels[key]) element.textContent = statusLabels[key];
    });

    var themeButton = document.querySelector('[data-theme-toggle]');
    if (themeButton) {
        setIcon(themeButton, preferred === 'dark' ? 'sun' : 'moon', false);
        themeButton.addEventListener('click', function () {
            var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            try { window.localStorage.setItem('torneios-theme', next); } catch (error) { /* preference is optional */ }
            themeButton.setAttribute('aria-label', next === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
            themeButton.setAttribute('title', themeButton.getAttribute('aria-label'));
            setIcon(themeButton, next === 'dark' ? 'sun' : 'moon', false);
        });
    }

    var sidebar = document.querySelector('[data-sidebar]');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    if (sidebar && sidebarToggle) {
        setIcon(sidebarToggle, 'menu', false);
        sidebarToggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('is-open');
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    var portalNav = document.querySelector('[data-portal-nav]');
    var portalNavToggle = document.querySelector('[data-portal-nav-toggle]');
    if (portalNav && portalNavToggle) {
        setIcon(portalNavToggle, 'menu', false);
        portalNavToggle.addEventListener('click', function () {
            var open = portalNav.classList.toggle('is-open');
            portalNavToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
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

    var portalBody = document.querySelector('[data-portal-primary]');
    if (portalBody) {
        ['primary', 'secondary', 'accent'].forEach(function (key) {
            var value = portalBody.dataset['portal' + key.charAt(0).toUpperCase() + key.slice(1)];
            if (value && /^#[0-9a-f]{6}$/i.test(value)) portalBody.style.setProperty('--portal-' + key, value);
        });
    }
}());
