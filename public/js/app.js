/* Xebia Up — app JS */
(function () {
    /* ============================================================
       Module side-panel (and skill detail loaded into the same slot)
       ============================================================ */
    const scrim = document.getElementById('panelScrim');
    const panel = document.getElementById('modulePanel');
    const content = document.getElementById('panelContent');

    function openPanel(slug) {
        loadIntoPanel('/modules/' + slug + '?inline=1', '/modules/' + slug);
    }

    function openSkillPanel(slug, fromModuleSlug) {
        var url = '/skill/' + slug + '?inline=1';
        if (fromModuleSlug) url += '&from=' + encodeURIComponent(fromModuleSlug);
        loadIntoPanel(url, '/skill/' + slug);
    }

    function loadIntoPanel(fetchUrl, fallbackUrl) {
        content.innerHTML = '<div class="panel-loading">Loading…</div>';
        panel.setAttribute('aria-hidden', 'false');
        panel.classList.add('open');
        scrim.classList.add('visible');
        document.body.classList.add('panel-open');
        content.scrollTop = 0;

        fetch(fetchUrl)
            .then(function (r) { return r.text(); })
            .then(function (html) {
                content.innerHTML = html;
                bindPanelClose();
            })
            .catch(function () {
                content.innerHTML = '<div class="panel-loading">Failed to load. <a href="' + fallbackUrl + '">Open page</a></div>';
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

    /* ============================================================
       Add-to-journey modal
       ============================================================ */
    const jmScrim = document.getElementById('journeyModalScrim');
    const jmModal = document.getElementById('journeyModal');
    const jmContent = document.getElementById('journeyModalContent');

    function openJourneyModal(slug) {
        if (!jmModal || !jmContent) return;
        jmContent.innerHTML = '<div class="journey-modal-loading">Loading…</div>';
        jmModal.setAttribute('aria-hidden', 'false');
        jmModal.classList.add('open');
        jmScrim.classList.add('visible');
        document.body.classList.add('journey-modal-open');

        fetch('/journeys/add-modal/' + encodeURIComponent(slug), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.text(); })
            .then(function (html) { jmContent.innerHTML = html; })
            .catch(function () { jmContent.innerHTML = '<div class="journey-modal-loading">Failed to load.</div>'; });
    }

    function closeJourneyModal() {
        if (!jmModal) return;
        jmModal.classList.remove('open');
        jmModal.setAttribute('aria-hidden', 'true');
        if (jmScrim) jmScrim.classList.remove('visible');
        document.body.classList.remove('journey-modal-open');
    }

    if (jmScrim) jmScrim.addEventListener('click', closeJourneyModal);

    /* ============================================================
       Click delegate — order matters
       ============================================================ */
    document.addEventListener('click', function (e) {
        // Modal close
        if (e.target.closest('[data-journey-modal-close]')) {
            e.preventDefault();
            closeJourneyModal();
            return;
        }

        // Modal "Create another" expand toggle
        var newToggle = e.target.closest('[data-journey-modal-new-toggle]');
        if (newToggle) {
            e.preventDefault();
            var newRow = newToggle.closest('[data-journey-modal-new]');
            if (newRow) {
                newRow.classList.add('journey-modal-row--new--expanded');
                newToggle.setAttribute('hidden', '');
                var form = newRow.querySelector('.journey-modal-new-form');
                if (form) {
                    form.removeAttribute('hidden');
                    var firstInput = form.querySelector('input[type="text"]');
                    if (firstInput) firstInput.focus();
                }
            }
            return;
        }

        // Modal "Create another" cancel
        var newCancel = e.target.closest('[data-journey-modal-new-cancel]');
        if (newCancel) {
            e.preventDefault();
            var newRow2 = newCancel.closest('[data-journey-modal-new]');
            if (newRow2) {
                newRow2.classList.remove('journey-modal-row--new--expanded');
                var toggle = newRow2.querySelector('[data-journey-modal-new-toggle]');
                if (toggle) toggle.removeAttribute('hidden');
                var form2 = newRow2.querySelector('.journey-modal-new-form');
                if (form2) form2.setAttribute('hidden', '');
            }
            return;
        }

        // "Add to journey" trigger (card and side-panel)
        var addBtn = e.target.closest('[data-add-journey]');
        if (addBtn) {
            e.preventDefault();
            openJourneyModal(addBtn.dataset.slug);
            return;
        }

        // Skill chip clicks open the skill detail in the side panel
        if (e.target.closest('[data-select-button], [data-select-form]')) return;

        var skill = e.target.closest('[data-skill-slug]');
        if (skill) {
            e.preventDefault();
            openSkillPanel(skill.dataset.skillSlug, skill.dataset.skillFrom || null);
            return;
        }

        // Module card → open detail side-panel
        var card = e.target.closest('[data-module-slug]');
        if (card) {
            e.preventDefault();
            openPanel(card.dataset.moduleSlug);
        }
    });

    /* ============================================================
       Form submit delegate
       ============================================================ */
    document.addEventListener('submit', function (e) {
        // Journey modal forms (add / remove / create-then-add)
        var jmForm = e.target.closest('[data-journey-modal-form]');
        if (jmForm) {
            e.preventDefault();
            submitModalForm(jmForm);
            return;
        }

        // Legacy in-card / detail-row select form (kept for the detail page remove buttons)
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
            var stillInActive = (data.selectedSlugs || []).indexOf(slug) !== -1;
            updateLegacySelectControls(slug, stillInActive);
            updateSelectionCount(data.count);
        })
        .catch(function () { form.submit(); });
    });

    function submitModalForm(form) {
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (data) {
            if (data.html && jmContent) {
                jmContent.innerHTML = data.html;
            }
            if (typeof data.count !== 'undefined') {
                updateSelectionCount(data.count);
            }
            if (typeof data.slug === 'string' && Array.isArray(data.anyJourneys)) {
                updateCardSelectedState(data.slug, data.anyJourneys.length > 0);
            }
        })
        .catch(function () {
            // Fall back to a full submit if AJAX fails so we don't lose the user's input.
            form.submit();
        });
    }

    function updateCardSelectedState(slug, isInAny) {
        var selectors = [
            'button[data-add-journey][data-slug="' + cssEscape(slug) + '"]'
        ];
        document.querySelectorAll(selectors.join(',')).forEach(function (btn) {
            btn.setAttribute('aria-pressed', isInAny ? 'true' : 'false');
            var inPanel = btn.classList.contains('panel-select');

            if (inPanel) {
                btn.classList.toggle('panel-select--on', isInAny);
                var label = btn.querySelector('.panel-select-label');
                if (label) label.textContent = isInAny ? 'In your journeys · review' : 'Add this module to a journey';
            } else {
                btn.classList.toggle('mcard-select--on', isInAny);
                var label2 = btn.querySelector('.mcard-select-label');
                if (label2) label2.textContent = isInAny ? 'Selected' : 'Add to journey';
                var wrap = btn.closest('.mcard-wrap');
                if (wrap) wrap.classList.toggle('mcard-wrap--selected', isInAny);
            }

            var svg = btn.querySelector('svg');
            if (svg) {
                svg.innerHTML = isInAny
                    ? '<polyline points="20 6 9 17 4 12"/>'
                    : '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>';
            }
        });
    }

    // Used by the legacy [data-select-button] forms (per-journey detail page remove rows).
    // The card never carries [data-select-button] any more, so this only runs for detail-row removes.
    function updateLegacySelectControls(slug, isSelected) {
        var rows = document.querySelectorAll('[data-select-button][data-slug="' + cssEscape(slug) + '"]');
        rows.forEach(function (btn) {
            var row = btn.closest('.sel-row');
            if (row && !isSelected) {
                row.remove();
            }
        });
    }

    function updateSelectionCount(n) {
        var el = document.querySelector('.selection-count');
        if (el && typeof n !== 'undefined') el.textContent = n;
    }

    function cssEscape(s) {
        if (window.CSS && window.CSS.escape) return CSS.escape(s);
        return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    /* ============================================================
       Drag-to-reorder
       ============================================================ */
    document.querySelectorAll('[data-sortable]').forEach(function (list) {
        if (typeof Sortable === 'undefined') return;
        Sortable.create(list, {
            animation: 150,
            ghostClass: 'sel-card--ghost',
            chosenClass: 'sel-card--chosen',
            dragClass: 'sel-card--drag',
            // Whole card is draggable, but clicks on the title/skill chips/remove button
            // still register as clicks (Sortable won't initiate a drag from them).
            filter: '[data-module-slug], [data-skill-slug], [data-select-button], button[type="submit"]',
            preventOnFilter: false,
            onEnd: function () { persistSelectionOrder(list); }
        });
    });

    function persistSelectionOrder(list) {
        var endpoint = list.dataset.reorderEndpoint || '/journey/reorder';
        var rows = list.querySelectorAll('[data-slug]');
        var slugs = Array.prototype.map.call(rows, function (r) { return r.dataset.slug; });

        var body = new FormData();
        slugs.forEach(function (s) { body.append('slugs[]', s); });

        fetch(endpoint, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).catch(function () { /* swallow; UI already updated */ });
    }

    /* ============================================================
       Debounced auto-saving meta forms (client, journey rename)
       ============================================================ */
    document.querySelectorAll('[data-meta-form]').forEach(function (form) {
        var status = form.querySelector('[data-meta-status]');
        var endpoint = form.dataset.metaEndpoint;
        var timer = null;
        if (!endpoint) return;

        form.addEventListener('submit', function (e) { e.preventDefault(); });

        function save(immediate) {
            if (status) status.textContent = '…';
            clearTimeout(timer);
            timer = setTimeout(function () {
                fetch(endpoint, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(function () { if (status) { status.textContent = 'Saved'; setTimeout(function () { status.textContent = ''; }, 1200); } })
                .catch(function () { if (status) status.textContent = 'Not saved'; });
            }, immediate ? 0 : 400);
        }

        form.addEventListener('input', function (e) {
            if (!e.target.matches('input[type="text"]')) return;
            save(false);
        });
        form.addEventListener('change', function (e) {
            if (!e.target.matches('select')) return;
            save(true);
        });
    });

    /* ============================================================
       Scrim + ESC for both overlays
       ============================================================ */
    if (scrim) scrim.addEventListener('click', closePanel);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Close whichever overlay is open. Modal first (it's on top).
            if (jmModal && jmModal.classList.contains('open')) {
                closeJourneyModal();
            } else {
                closePanel();
            }
        }
    });

    /* ============================================================
       Filter form auto-submit — preserve scroll position
       ============================================================ */
    const filterForm = document.querySelector('[data-filter-form]');
    if (filterForm) {
        const SCROLL_KEY = 'journey:scroll:' + location.pathname;

        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const savedScroll = sessionStorage.getItem(SCROLL_KEY);
        if (savedScroll !== null) {
            sessionStorage.removeItem(SCROLL_KEY);
            const y = parseInt(savedScroll, 10) || 0;
            window.scrollTo(0, y);
            requestAnimationFrame(function () { window.scrollTo(0, y); });
        }

        filterForm.addEventListener('change', function (e) {
            if (e.target && e.target.matches('input[type="checkbox"]')) {
                sessionStorage.setItem(SCROLL_KEY, String(window.scrollY));
                filterForm.submit();
            }
        });
    }

    /* ============================================================
       Toast helper
       ============================================================ */
    window.showToast = function (msg) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('visible');
        setTimeout(function () { toast.classList.remove('visible'); }, 2800);
    };
})();
