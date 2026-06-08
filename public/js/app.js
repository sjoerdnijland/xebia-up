/* Xebia Up — app JS */
(function () {
    const scrim = document.getElementById('panelScrim');
    const panel = document.getElementById('modulePanel');
    const content = document.getElementById('panelContent');

    function openPanel(slug) {
        content.innerHTML = '<div class="panel-loading">Loading…</div>';
        panel.setAttribute('aria-hidden', 'false');
        panel.classList.add('open');
        scrim.classList.add('visible');
        document.body.classList.add('panel-open');

        fetch('/modules/' + slug + '?inline=1')
            .then(function (r) { return r.text(); })
            .then(function (html) {
                content.innerHTML = html;
                bindPanelClose();
            })
            .catch(function () {
                content.innerHTML = '<div class="panel-loading">Failed to load. <a href="/modules/' + slug + '">Open page</a></div>';
            });
    }

    function closePanel() {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
        scrim.classList.remove('visible');
        document.body.classList.remove('panel-open');
    }

    function bindPanelClose() {
        var btn = document.getElementById('panelClose');
        if (btn) btn.addEventListener('click', closePanel);
    }

    // Module card clicks
    document.addEventListener('click', function (e) {
        var card = e.target.closest('[data-module-slug]');
        if (card) {
            e.preventDefault();
            openPanel(card.dataset.moduleSlug);
        }
    });

    // Scrim click
    if (scrim) scrim.addEventListener('click', closePanel);

    // ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });

    // Toast helper
    window.showToast = function (msg) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('visible');
        setTimeout(function () { toast.classList.remove('visible'); }, 2800);
    };
})();
