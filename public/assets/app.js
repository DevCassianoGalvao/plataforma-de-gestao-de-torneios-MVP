(function () {
    'use strict';

    var root = document.body;
    var savedTheme = null;
    try { savedTheme = window.localStorage.getItem('torneios-theme'); } catch (error) { savedTheme = null; }
    var preferred = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    if (root.classList.contains('public-portal') && !savedTheme) { preferred = 'light'; }
    root.dataset.theme = preferred;

    var themeButton = document.querySelector('[data-theme-toggle]');
    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            try { window.localStorage.setItem('torneios-theme', next); } catch (error) { /* preference is optional */ }
            themeButton.setAttribute('aria-label', next === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
            themeButton.setAttribute('title', themeButton.getAttribute('aria-label'));
        });
    }

    var sidebar = document.querySelector('[data-sidebar]');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('is-open');
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    var portalNav = document.querySelector('[data-portal-nav]');
    var portalNavToggle = document.querySelector('[data-portal-nav-toggle]');
    if (portalNav && portalNavToggle) {
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
