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

    // Module card clicks (ignore the in-card select toggle so its submit isn't hijacked)
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-select-button], [data-select-form]')) return;
        var card = e.target.closest('[data-module-slug]');
        if (card) {
            e.preventDefault();
            openPanel(card.dataset.moduleSlug);
        }
    });

    // AJAX submit for in-company select / deselect forms
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-select-form]');
        if (!form) return;
        var btn = form.querySelector('[data-select-button]');
        if (!btn) return;

        e.preventDefault();

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (data) {
            var slug = btn.dataset.slug;
            var nowSelected = (data.selectedSlugs || []).indexOf(slug) !== -1;
            updateSelectControls(slug, nowSelected);
            updateSelectionCount(data.count);
        })
        .catch(function () {
            form.submit();
        });
    });

    function updateSelectControls(slug, isSelected) {
        var buttons = document.querySelectorAll('[data-select-button][data-slug="' + cssEscape(slug) + '"]');
        buttons.forEach(function (btn) {
            var form = btn.closest('form');
            var inPanel = btn.classList.contains('panel-select');
            btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

            if (inPanel) {
                btn.classList.toggle('panel-select--on', isSelected);
                var label = btn.querySelector('.panel-select-label');
                if (label) label.textContent = isSelected ? 'Selected · click to remove' : 'Add this module to the journey';
            } else {
                btn.classList.toggle('mcard-select--on', isSelected);
                var label2 = btn.querySelector('.mcard-select-label');
                if (label2) label2.textContent = isSelected ? 'Selected' : 'Add to journey';
                var wrap = btn.closest('.mcard-wrap');
                if (wrap) wrap.classList.toggle('mcard-wrap--selected', isSelected);
            }

            var svg = btn.querySelector('svg');
            if (svg) {
                svg.innerHTML = isSelected
                    ? '<polyline points="20 6 9 17 4 12"/>'
                    : '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>';
            }

            if (form) {
                form.action = isSelected ? '/journey/deselect' : '/journey/select';
            }
        });
    }

    function updateSelectionCount(n) {
        var el = document.querySelector('.selection-count');
        if (el) el.textContent = n;
    }

    function cssEscape(s) {
        if (window.CSS && window.CSS.escape) return CSS.escape(s);
        return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    // Sortable lists on the overview page
    document.querySelectorAll('[data-sortable]').forEach(function (list) {
        if (typeof Sortable === 'undefined') return;
        Sortable.create(list, {
            handle: '.sel-handle',
            animation: 150,
            ghostClass: 'sel-row--ghost',
            chosenClass: 'sel-row--chosen',
            onEnd: function () { persistSelectionOrder(); }
        });
    });

    function persistSelectionOrder() {
        var rows = document.querySelectorAll('[data-sortable] [data-slug]');
        var slugs = Array.prototype.map.call(rows, function (r) { return r.dataset.slug; });

        var body = new FormData();
        slugs.forEach(function (s) { body.append('slugs[]', s); });

        fetch('/journey/reorder', {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).catch(function () { /* swallow; UI already updated */ });
    }

    // Auto-saving meta forms (client name, role name) — debounced
    document.querySelectorAll('[data-meta-form]').forEach(function (form) {
        var input = form.querySelector('input[name="name"]');
        var status = form.querySelector('[data-meta-status]');
        var endpoint = form.dataset.metaEndpoint;
        var timer = null;

        form.addEventListener('submit', function (e) { e.preventDefault(); });
        if (!input || !endpoint) return;

        input.addEventListener('input', function () {
            if (status) status.textContent = '…';
            clearTimeout(timer);
            timer = setTimeout(function () {
                var body = new FormData();
                body.append('name', input.value);
                fetch(endpoint, {
                    method: 'POST',
                    body: body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(function () { if (status) { status.textContent = 'Saved'; setTimeout(function () { status.textContent = ''; }, 1200); } })
                .catch(function () { if (status) status.textContent = 'Not saved'; });
            }, 400);
        });
    });

    // Scrim click
    if (scrim) scrim.addEventListener('click', closePanel);

    // ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });

    // Filter form auto-submit
    const filterForm = document.querySelector('[data-filter-form]');
    if (filterForm) {
        filterForm.addEventListener('change', function (e) {
            if (e.target && e.target.matches('input[type="checkbox"]')) {
                filterForm.submit();
            }
        });
    }

    // Toast helper
    window.showToast = function (msg) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('visible');
        setTimeout(function () { toast.classList.remove('visible'); }, 2800);
    };
})();
