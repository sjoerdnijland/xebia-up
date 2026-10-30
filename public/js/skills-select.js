/* Xebia Up — Skills model: interactive "build a skills profile" mode
 *
 * Users switch on selection mode, click a matrix cell to set a target level
 * per skill (levels are cumulative), and open an overview with advised modules.
 * State lives in the URL hash (#profile=skill-slug:3,…) so a selection can be
 * shared, with localStorage as a per-browser convenience.
 */
(function () {
    'use strict';

    var dataEl = document.getElementById('skillSelectorData');
    if (!dataEl) return;

    var DATA;
    try { DATA = JSON.parse(dataEl.textContent); } catch (e) { return; }

    var LEVELS = DATA.levels || [];                 // [{slug,name,depth,color}]
    var SKILLS = DATA.skills || {};                 // slug → skill
    var MODULES = DATA.modules || {};               // slug → module

    /* Journeys (only present in in-company mode) */
    var JOURNEYS = DATA.journeys || null;          // {activeId, journeys:[{id,name,audience,clientId,clientName,slugs}], clients:[{id,name}]}
    var TARGET_KEY = 'up:skills-target-journey';
    var targetJourneyId = null;
    var showNewJourney = false;
    var busy = false;

    var levelBySlug = {}, levelByDepth = {};
    LEVELS.forEach(function (l) { levelBySlug[l.slug] = l; levelByDepth[l.depth] = l; });

    var STORE_KEY = 'up:skills-profile:' + (DATA.category || 'default');
    var HASH_KEY = 'profile';

    var body = document.body;
    var toggleBtn = document.querySelector('[data-ss-toggle]');
    var toggleLabel = document.querySelector('[data-ss-toggle-label]');
    var intro = document.querySelector('[data-ss-intro]');
    var tray = document.querySelector('[data-ss-tray]');
    var countEls = Array.prototype.slice.call(document.querySelectorAll('[data-ss-count]'));
    var countLabelEl = document.querySelector('[data-ss-count-label]');
    var trayLevelsEl = document.querySelector('[data-ss-tray-levels]');
    var openBtns = Array.prototype.slice.call(document.querySelectorAll('[data-ss-open]'));
    var introCta = document.querySelector('.ss-intro [data-ss-open]');
    var doneBtn = document.querySelector('[data-ss-done]');
    var clearBtn = document.querySelector('[data-ss-clear]');
    var drawer = document.querySelector('[data-ss-drawer]');
    var drawerContent = document.querySelector('[data-ss-drawer-content]');
    var scrim = document.querySelector('[data-ss-scrim]');

    /** slug → target depth */
    var selection = {};
    var modeOn = false;

    /* ------------------------------------------------------------
       State: load / save
       ------------------------------------------------------------ */
    function parseProfile(str) {
        var out = {};
        if (!str) return out;
        str.split(',').forEach(function (pair) {
            var i = pair.lastIndexOf(':');
            if (i < 1) return;
            var slug = decodeURIComponent(pair.slice(0, i));
            var depth = parseInt(pair.slice(i + 1), 10);
            if (SKILLS[slug] && levelByDepth[depth]) out[slug] = depth;
        });
        return out;
    }

    function serialize() {
        return orderedSelection().map(function (slug) {
            return encodeURIComponent(slug) + ':' + selection[slug];
        }).join(',');
    }

    function readHash() {
        var m = location.hash.match(new RegExp('[#&]' + HASH_KEY + '=([^&]*)'));
        return m ? m[1] : null;
    }

    function save() {
        var str = serialize();
        try {
            if (str) localStorage.setItem(STORE_KEY, str);
            else localStorage.removeItem(STORE_KEY);
        } catch (e) { /* storage unavailable — URL still carries state */ }
        try {
            var url = location.pathname + location.search + (str ? '#' + HASH_KEY + '=' + str : '');
            history.replaceState(null, '', url);
        } catch (e) { /* ignore */ }
    }

    function load() {
        var fromHash = readHash();
        if (fromHash !== null) return parseProfile(fromHash);
        try { return parseProfile(localStorage.getItem(STORE_KEY)); } catch (e) { return {}; }
    }

    function orderedSelection() {
        return Object.keys(selection).sort(function (a, b) {
            return SKILLS[a].order - SKILLS[b].order;
        });
    }

    function shareUrl() {
        var str = serialize();
        return location.origin + location.pathname + (str ? '#' + HASH_KEY + '=' + str : '');
    }

    /* ------------------------------------------------------------
       Matrix rendering
       ------------------------------------------------------------ */
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-ss-skill]'));

    function paintRow(row) {
        var slug = row.dataset.ssSkill;
        var target = selection[slug] || 0;
        var skill = SKILLS[slug];
        row.classList.toggle('ss-row--on', target > 0);

        var badge = row.querySelector('[data-ss-skill-level]');
        if (badge) {
            var lvl = levelByDepth[target];
            badge.textContent = lvl ? lvl.name : '';
            badge.style.setProperty('--lvl-color', lvl ? lvl.color : '');
        }

        row.querySelectorAll('[data-ss-level]').forEach(function (cell) {
            var lvl = levelBySlug[cell.dataset.ssLevel];
            var d = lvl ? lvl.depth : 0;
            cell.classList.toggle('ss-cell--target', target > 0 && d === target);
            cell.classList.toggle('ss-cell--below', target > 0 && d < target);
            cell.classList.toggle('ss-cell--above', target > 0 && d > target);
            if (modeOn) {
                cell.setAttribute('tabindex', '0');
                cell.setAttribute('role', 'button');
                cell.setAttribute('aria-pressed', d === target ? 'true' : 'false');
                cell.setAttribute('aria-label', (skill ? skill.name : slug) + ' — ' +
                    (d === target ? 'clear target level' : 'set target level to ' + (lvl ? lvl.name : '')));
            } else {
                cell.removeAttribute('tabindex');
                cell.removeAttribute('role');
                cell.removeAttribute('aria-pressed');
                cell.removeAttribute('aria-label');
            }
        });
    }

    function paintColumns() {
        document.querySelectorAll('[data-ss-col]').forEach(function (h) {
            if (modeOn) {
                h.setAttribute('tabindex', '0');
                h.setAttribute('role', 'button');
            } else {
                h.removeAttribute('tabindex');
                h.removeAttribute('role');
            }
        });
    }

    function paintTray() {
        var slugs = orderedSelection();
        var n = slugs.length;
        countEls.forEach(function (el) { el.textContent = n; });
        countLabelEl.textContent = n === 1 ? 'skill selected' : 'skills selected';
        openBtns.forEach(function (b) { b.disabled = n === 0; });
        if (introCta) introCta.hidden = n === 0;
        clearBtn.hidden = n === 0;
        doneBtn.textContent = modeOn ? 'Done selecting' : 'Edit selection';

        // Mini per-level breakdown
        var perLevel = {};
        slugs.forEach(function (s) { perLevel[selection[s]] = (perLevel[selection[s]] || 0) + 1; });
        trayLevelsEl.innerHTML = LEVELS.filter(function (l) { return perLevel[l.depth]; }).map(function (l) {
            return '<span class="ss-tray-lvl" style="--lvl-color:' + esc(l.color) + '" title="' + esc(l.name) + '">' +
                '<span class="ss-tray-lvl-dot"></span>' + perLevel[l.depth] + ' ' + esc(l.name) + '</span>';
        }).join('');

        tray.hidden = !(modeOn || n > 0);
        body.classList.toggle('ss-tray-visible', !tray.hidden);
    }

    function paintAll() {
        rows.forEach(paintRow);
        paintColumns();
        paintTray();
    }

    function setMode(on) {
        modeOn = on;
        body.classList.toggle('ss-mode', on);
        if (toggleBtn) toggleBtn.setAttribute('aria-checked', on ? 'true' : 'false');
        if (toggleLabel) toggleLabel.textContent = on ? 'On' : 'Off';
        if (intro) {
            intro.classList.toggle('ss-intro--on', on);
            var offTxt = intro.querySelector('[data-ss-intro-off]');
            var onTxt = intro.querySelector('[data-ss-intro-on]');
            if (offTxt) offTxt.hidden = on;
            if (onTxt) onTxt.hidden = !on;
        }
        paintAll();
    }

    function setSkill(slug, depth) {
        if (!SKILLS[slug]) return;
        if (!depth || selection[slug] === depth) delete selection[slug];
        else selection[slug] = depth;
        var row = document.querySelector('[data-ss-skill="' + cssEscape(slug) + '"]');
        if (row) paintRow(row);
        paintTray();
        save();
    }

    function setDomainLevel(domainEl, depth) {
        var domainRows = domainEl.querySelectorAll('[data-ss-skill]');
        var allAtLevel = Array.prototype.every.call(domainRows, function (r) {
            return selection[r.dataset.ssSkill] === depth;
        });
        domainRows.forEach(function (r) {
            if (allAtLevel) delete selection[r.dataset.ssSkill];
            else selection[r.dataset.ssSkill] = depth;
            paintRow(r);
        });
        paintTray();
        save();
    }

    /* ------------------------------------------------------------
       Advice engine
       ------------------------------------------------------------ */
    function buildAdvice() {
        var slugs = orderedSelection();
        var advised = {};     // module slug → {module, covers:[{skill, target, reaches}]}
        var nextStep = {};    // module slug → {module, covers:[…]} (one level above target)
        var perSkill = [];

        slugs.forEach(function (slug) {
            var skill = SKILLS[slug];
            var target = selection[slug];
            var mods = (skill.modules || []).map(function (m) { return MODULES[m]; }).filter(Boolean);
            var within = mods.filter(function (m) { return m.depth <= target; });
            var atTarget = within.filter(function (m) { return m.depth === target; });
            var above = mods.filter(function (m) { return m.depth > target; });

            within.forEach(function (m) {
                advised[m.slug] = advised[m.slug] || { module: m, covers: [] };
                advised[m.slug].covers.push({ skill: skill, target: target, reaches: m.depth === target });
            });
            above.filter(function (m) { return m.depth === target + 1; }).forEach(function (m) {
                nextStep[m.slug] = nextStep[m.slug] || { module: m, covers: [] };
                nextStep[m.slug].covers.push({ skill: skill, target: target });
            });

            var status;
            if (within.length === 0) status = 'gap';
            else if (atTarget.length === 0) status = 'partial';
            else status = 'covered';

            perSkill.push({
                skill: skill, target: target, within: sortModules(within),
                above: sortModules(above), status: status
            });
        });

        Object.keys(nextStep).forEach(function (s) { if (advised[s]) delete nextStep[s]; });

        var advisedList = sortEntries(Object.keys(advised).map(function (k) { return advised[k]; }));
        var nextList = sortEntries(Object.keys(nextStep).map(function (k) { return nextStep[k]; }));
        var hours = advisedList.reduce(function (sum, e) { return sum + (e.module.hours || 0); }, 0);

        return {
            slugs: slugs, perSkill: perSkill, advised: advisedList, next: nextList, hours: hours,
            gaps: perSkill.filter(function (p) { return p.status === 'gap'; }).length,
            partial: perSkill.filter(function (p) { return p.status === 'partial'; }).length
        };
    }

    function sortModules(list) {
        return list.slice().sort(function (a, b) {
            return (a.depth - b.depth) || (a.position - b.position) || a.title.localeCompare(b.title);
        });
    }

    function sortEntries(list) {
        return list.sort(function (a, b) {
            var x = a.module, y = b.module;
            return (x.depth - y.depth) || (b.covers.length - a.covers.length) ||
                (x.position - y.position) || x.title.localeCompare(y.title);
        });
    }

    /* ------------------------------------------------------------
       Overview rendering
       ------------------------------------------------------------ */
    function formatHours(h) {
        if (!h) return '0 hours';
        var txt = h + (h === 1 ? ' hour' : ' hours');
        if (h >= 8) {
            var days = Math.round((h / 8) * 2) / 2;
            txt += ' · ≈ ' + days + (days === 1 ? ' day' : ' days');
        }
        return txt;
    }

    function levelPill(depth) {
        var l = levelByDepth[depth];
        if (!l) return '';
        return '<span class="ss-lvl" style="--lvl-color:' + esc(l.color) + '">' +
            '<span class="ss-lvl-num">' + l.depth + '</span>' + esc(l.name) + '</span>';
    }

    function skillChip(skill, extra) {
        return '<button type="button" class="ss-chip" data-skill-slug="' + esc(skill.slug) + '" style="--skill-color:' + esc(skill.color) + '">' +
            '<span class="ss-chip-dot"></span><span class="ss-chip-name">' + esc(skill.name) + '</span>' +
            (extra || '') + '</button>';
    }

    function moduleAction(slug) {
        var j = targetJourney();
        if (!j) return '';
        var inIt = j.slugs.indexOf(slug) !== -1;
        if (inIt) {
            return '<div class="ss-jact ss-jact--in">' +
                '<span class="ss-jin"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>In journey</span>' +
                '<button type="button" class="ss-jremove" data-ss-jremove="' + esc(slug) + '"' + (busy ? ' disabled' : '') + '>Remove</button>' +
            '</div>';
        }
        return '<div class="ss-jact">' +
            '<button type="button" class="ss-jadd" data-ss-jadd="' + esc(slug) + '"' + (busy ? ' disabled' : '') + '>' +
                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add to journey</button>' +
        '</div>';
    }

    function moduleRow(entry, variant) {
        var m = entry.module;
        var covers = entry.covers.map(function (c) {
            var tag = '';
            if (variant === 'advised') {
                tag = c.reaches
                    ? '<span class="ss-chip-tag ss-chip-tag--reach" title="Reaches the target level">target</span>'
                    : '<span class="ss-chip-tag" title="Builds the foundation below the target level">foundation</span>';
            }
            return skillChip(c.skill, tag);
        }).join('');

        var j = targetJourney();
        var inIt = j && j.slugs.indexOf(m.slug) !== -1;
        return '<li class="ss-mod' + (inIt ? ' ss-mod--in' : '') + '">' +
            '<div class="ss-mod-main">' +
                '<div class="ss-mod-meta">' + levelPill(m.depth) +
                    (m.type ? '<span class="ss-mod-type">' + esc(m.type) + '</span>' : '') +
                    '<span class="ss-mod-dur"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' + esc(m.duration) + '</span>' +
                '</div>' +
                '<button type="button" class="ss-mod-title" data-module-slug="' + esc(m.slug) + '">' + esc(m.title) +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>' +
                '<p class="ss-mod-desc">' + esc(m.description) + '</p>' +
                '<div class="ss-mod-covers"><span class="ss-mod-covers-label">Develops</span>' + covers + '</div>' +
            '</div>' +
            moduleAction(m.slug) +
        '</li>';
    }

    /* ------------------------------------------------------------
       Journey targeting
       ------------------------------------------------------------ */
    function journeyById(id) {
        if (!JOURNEYS) return null;
        for (var i = 0; i < JOURNEYS.journeys.length; i++) {
            if (JOURNEYS.journeys[i].id === id) return JOURNEYS.journeys[i];
        }
        return null;
    }

    function targetJourney() { return journeyById(targetJourneyId); }

    function pickInitialJourney() {
        if (!JOURNEYS) return;
        var stored = null;
        try { stored = parseInt(localStorage.getItem(TARGET_KEY), 10); } catch (e) { /* ignore */ }
        if (journeyById(targetJourneyId)) return;
        if (stored && journeyById(stored)) targetJourneyId = stored;
        else if (journeyById(JOURNEYS.activeId)) targetJourneyId = JOURNEYS.activeId;
        else targetJourneyId = JOURNEYS.journeys.length ? JOURNEYS.journeys[0].id : null;
    }

    function setTargetJourney(id) {
        targetJourneyId = id;
        try { if (id) localStorage.setItem(TARGET_KEY, String(id)); } catch (e) { /* ignore */ }
    }

    function journeySection(a) {
        if (!JOURNEYS) return '';
        var list = JOURNEYS.journeys;
        var j = targetJourney();
        var html = '<section class="ss-jsel' + (j ? '' : ' ss-jsel--empty') + '">' +
            '<div class="ss-jsel-head">' +
                '<span class="ss-jsel-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>' +
                '<div><h3 class="ss-jsel-title">Which journey is this for?</h3>' +
                '<p class="ss-jsel-lede">Pick the journey you are building. Each advised module then shows whether it is already in it.</p></div>' +
            '</div>';

        if (list.length && !showNewJourney) {
            html += '<div class="ss-jsel-row">' +
                '<label class="ss-select"><span class="sr-only">Journey</span><select data-ss-journey>';
            var groups = {};
            var order = [];
            list.forEach(function (x) {
                if (!groups[x.clientName]) { groups[x.clientName] = []; order.push(x.clientName); }
                groups[x.clientName].push(x);
            });
            order.forEach(function (client) {
                html += '<optgroup label="' + esc(client) + '">';
                groups[client].forEach(function (x) {
                    html += '<option value="' + x.id + '"' + (x.id === targetJourneyId ? ' selected' : '') + '>' +
                        esc(x.name) + (x.audience ? ' — ' + esc(x.audience) : '') + ' (' + x.slugs.length + ')</option>';
                });
                html += '</optgroup>';
            });
            html += '</select></label>' +
                '<button type="button" class="btn-ghost" data-ss-jnew>+ New journey</button>' +
                (j ? '<a class="btn-ghost" href="/journeys/' + j.id + '">Open journey ↗</a>' : '') +
            '</div>';

            if (j) {
                var advisedSlugs = a.advised.map(function (e) { return e.module.slug; });
                var inCount = advisedSlugs.filter(function (s) { return j.slugs.indexOf(s) !== -1; }).length;
                var missing = advisedSlugs.length - inCount;
                html += '<div class="ss-jsel-status' + (missing === 0 && advisedSlugs.length ? ' ss-jsel-status--done' : '') + '">' +
                    '<div class="ss-jsel-progress"><span style="width:' + (advisedSlugs.length ? Math.round(inCount / advisedSlugs.length * 100) : 0) + '%"></span></div>' +
                    '<p><strong>' + inCount + ' of ' + advisedSlugs.length + '</strong> advised modules are in <strong>' + esc(j.name) + '</strong>' +
                    (missing === 0 && advisedSlugs.length ? ' — all set.' : '.') + '</p>' +
                    (missing > 0 ? '<button type="button" class="btn-primary" data-ss-jaddall' + (busy ? ' disabled' : '') + '>Add the ' + missing + ' missing module' + (missing === 1 ? '' : 's') + '</button>' : '') +
                '</div>';
            }
        } else {
            html += newJourneyForm(list.length > 0);
        }
        html += '</section>';
        return html;
    }

    function newJourneyForm(canCancel) {
        var clients = (JOURNEYS && JOURNEYS.clients) || [];
        var opts = clients.map(function (c) { return '<option value="' + c.id + '">' + esc(c.name) + '</option>'; }).join('');
        return '<form class="ss-jnew" data-ss-jnew-form>' +
            (canCancel ? '' : '<p class="ss-jnew-note">There are no journeys yet — create one to collect these modules.</p>') +
            '<div class="ss-jnew-grid">' +
                '<label><span>Journey name</span><input type="text" name="name" required maxlength="120" placeholder="e.g. AI upskilling — product team"></label>' +
                '<label><span>Audience <em>(optional)</em></span><input type="text" name="audience" maxlength="120" placeholder="e.g. Product Owners"></label>' +
                '<label><span>Client</span><select name="clientId" data-ss-jclient>' + opts +
                    '<option value=""' + (clients.length ? '' : ' selected') + '>+ New client…</option></select></label>' +
                '<label data-ss-jclientname' + (clients.length ? ' hidden' : '') + '><span>New client name</span><input type="text" name="clientName" maxlength="120"></label>' +
            '</div>' +
            '<div class="ss-jnew-actions">' +
                '<button type="submit" class="btn-primary">Create journey</button>' +
                (canCancel ? '<button type="button" class="btn-ghost" data-ss-jnew-cancel>Cancel</button>' : '') +
                '<span class="ss-jnew-err" data-ss-jnew-err></span>' +
            '</div>' +
        '</form>';
    }

    function post(url, data) {
        var body = new FormData();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        return fetch(url, {
            method: 'POST', body: body, credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) {
                if (!r.ok) throw new Error(d.error || 'Request failed');
                return d;
            });
        });
    }

    function refreshJourneys() {
        return fetch('/journeys/summary', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d) { JOURNEYS = d; pickInitialJourney(); } })
            .catch(function () { /* keep what we have */ });
    }

    function updateHeaderCount(n) {
        var el = document.querySelector('.selection-count');
        if (el && typeof n !== 'undefined') el.textContent = n;
    }

    function setInJourney(slug, add) {
        var j = targetJourney();
        if (!j) return Promise.resolve();
        return post('/journeys/' + j.id + (add ? '/select' : '/deselect'), { slug: slug }).then(function () {
            var i = j.slugs.indexOf(slug);
            if (add && i === -1) j.slugs.push(slug);
            if (!add && i !== -1) j.slugs.splice(i, 1);
        });
    }

    function withBusy(promise, okMsg) {
        busy = true; renderOverview();
        return promise.then(function () {
            if (okMsg && window.showToast) window.showToast(okMsg);
        }).catch(function () {
            if (window.showToast) window.showToast('Could not update the journey — please try again');
        }).then(function () { busy = false; renderOverview(); });
    }

    function renderOverview() {
        var a = buildAdvice();
        var html = '';

        html += '<div class="ss-d-head">' +
            '<button class="panel-close" type="button" data-ss-close aria-label="Close overview">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>' +
            '<div class="eyebrow">' + esc(DATA.categoryName || '') + ' · Skills profile</div>' +
            '<h2 class="ss-d-title" id="ssDrawerTitle">Your selection &amp; advised modules</h2>' +
            '<div class="ss-stats">' +
                stat(a.slugs.length, a.slugs.length === 1 ? 'skill selected' : 'skills selected') +
                stat(a.advised.length, a.advised.length === 1 ? 'module advised' : 'modules advised') +
                stat(formatHours(a.hours), 'total learning time', true) +
                stat(a.gaps, a.gaps === 1 ? 'skill without a module' : 'skills without a module', false, a.gaps > 0) +
            '</div>' +
            '<div class="ss-d-actions">' +
                '<button type="button" class="btn-ghost" data-ss-edit>' +
                    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Edit selection</button>' +
                '<button type="button" class="btn-ghost" data-ss-copy>' +
                    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Copy share link</button>' +
                '<button type="button" class="btn-ghost" data-ss-print>' +
                    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Print / PDF</button>' +
            '</div>' +
        '</div>';

        html += '<div class="ss-d-body">';
        html += journeySection(a);

        // --- Advised learning path, grouped per level
        html += '<section class="ss-sec">' +
            '<h3>Advised learning path</h3>' +
            '<p class="ss-sec-lede">Modules that build each selected skill up to its target level. Levels are cumulative, so foundation modules come first.</p>';
        if (a.advised.length === 0) {
            html += '<div class="ss-empty">None of the selected skills is covered by a module up to the chosen level yet. See the per-skill advice below.</div>';
        } else {
            LEVELS.forEach(function (l) {
                var group = a.advised.filter(function (e) { return e.module.depth === l.depth; });
                if (!group.length) return;
                var hrs = group.reduce(function (s, e) { return s + (e.module.hours || 0); }, 0);
                html += '<div class="ss-step" style="--lvl-color:' + esc(l.color) + '">' +
                    '<div class="ss-step-head"><span class="ss-step-num">' + l.depth + '</span>' +
                    '<span class="ss-step-name">' + esc(l.name) + '</span>' +
                    '<span class="ss-step-meta">' + group.length + (group.length === 1 ? ' module' : ' modules') + ' · ' + esc(formatHours(hrs)) + '</span></div>' +
                    '<ul class="ss-mods">' + group.map(function (e) { return moduleRow(e, 'advised'); }).join('') + '</ul>' +
                '</div>';
            });
        }
        html += '</section>';

        // --- Per-skill advice
        html += '<section class="ss-sec"><h3>Per skill</h3>' +
            '<p class="ss-sec-lede">What the target level means for each skill, and how well the current module catalogue covers it.</p>' +
            '<ul class="ss-skills">';
        a.perSkill.forEach(function (p) {
            var lvl = levelByDepth[p.target];
            var desc = (p.skill.descriptions || {})[lvl.slug] || '';
            var statusHtml;
            if (p.status === 'covered') {
                statusHtml = '<span class="ss-status ss-status--ok">Covered</span>';
            } else if (p.status === 'partial') {
                statusHtml = '<span class="ss-status ss-status--partial">Foundation only</span>';
            } else {
                statusHtml = '<span class="ss-status ss-status--gap">No module yet</span>';
            }

            var advice;
            if (p.status === 'gap') {
                advice = p.above.length
                    ? 'No module builds this skill up to <strong>' + esc(lvl.name) + '</strong>. The closest option sits at a higher level: ' + moduleLinks(p.above.slice(0, 2)) + '. Otherwise, grow it through coaching, on-the-job practice or a tailored session.'
                    : 'No module covers this skill yet. Grow it through coaching, on-the-job practice or a tailored session — or ask the Academy about a custom module.';
            } else if (p.status === 'partial') {
                advice = 'Foundation: ' + moduleLinks(p.within) + '. No module targets <strong>' + esc(lvl.name) + '</strong> directly yet — complement with practice on real work' +
                    (p.above.length ? ' or stretch into ' + moduleLinks(p.above.slice(0, 1)) : '') + '.';
            } else {
                advice = 'Advised: ' + moduleLinks(p.within) + '.';
            }

            html += '<li class="ss-skill" style="--skill-color:' + esc(p.skill.color) + '">' +
                '<div class="ss-skill-head">' +
                    '<span class="ss-skill-domain">' + esc(p.skill.domain) + '</span>' + statusHtml +
                '</div>' +
                '<div class="ss-skill-title"><button type="button" class="ss-skill-name" data-skill-slug="' + esc(p.skill.slug) + '">' + esc(p.skill.name) + '</button>' + levelPill(p.target) + '</div>' +
                (desc ? '<p class="ss-skill-desc"><span class="ss-skill-desc-label">At ' + esc(lvl.name) + ':</span> ' + esc(desc) + '</p>' : '') +
                '<p class="ss-skill-advice">' + advice + '</p>' +
                '<button type="button" class="ss-skill-remove" data-ss-remove="' + esc(p.skill.slug) + '">Remove</button>' +
            '</li>';
        });
        html += '</ul></section>';

        // --- Next steps
        if (a.next.length) {
            html += '<section class="ss-sec">' +
                '<details class="ss-next"><summary><h3>Ready to go further? <span class="ss-next-count">' + a.next.length + '</span></h3>' +
                '<span class="ss-sec-lede">Modules one level above the targets you set.</span></summary>' +
                '<ul class="ss-mods">' + a.next.map(function (e) { return moduleRow(e, 'next'); }).join('') + '</ul>' +
                '</details></section>';
        }

        html += '</div>';
        var keepScroll = drawerContent.scrollTop;
        drawerContent.innerHTML = html;
        drawerContent.scrollTop = keepScroll;
    }

    function stat(value, label, small, warn) {
        return '<div class="ss-stat' + (warn ? ' ss-stat--warn' : '') + '">' +
            '<span class="ss-stat-value' + (small ? ' ss-stat-value--sm' : '') + '">' + esc(String(value)) + '</span>' +
            '<span class="ss-stat-label">' + esc(label) + '</span></div>';
    }

    function moduleLinks(list) {
        return list.map(function (m) {
            return '<button type="button" class="ss-inline-mod" data-module-slug="' + esc(m.slug) + '">' + esc(m.title) + '</button>';
        }).join(', ');
    }

    /* ------------------------------------------------------------
       Drawer open / close
       ------------------------------------------------------------ */
    function openDrawer() {
        if (!Object.keys(selection).length) return;
        pickInitialJourney();
        renderOverview();
        if (JOURNEYS) refreshJourneys().then(function () { if (drawerOpen()) renderOverview(); });
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        scrim.classList.add('visible');
        body.classList.add('ss-drawer-open');
        drawerContent.scrollTop = 0;
        var close = drawer.querySelector('[data-ss-close]');
        if (close) close.focus({ preventScroll: true });
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        scrim.classList.remove('visible');
        body.classList.remove('ss-drawer-open');
    }

    function drawerOpen() { return drawer.classList.contains('open'); }

    /* ------------------------------------------------------------
       Events
       ------------------------------------------------------------ */
    if (toggleBtn) toggleBtn.addEventListener('click', function () {
        setMode(!modeOn);
        if (modeOn && window.showToast) window.showToast('Selection mode on — click a level cell to set a target');
    });
    doneBtn.addEventListener('click', function () { setMode(!modeOn); });
    openBtns.forEach(function (b) { b.addEventListener('click', openDrawer); });
    clearBtn.addEventListener('click', function () {
        selection = {};
        save();
        paintAll();
        if (!modeOn) setMode(false);
    });
    scrim.addEventListener('click', closeDrawer);

    function activateCell(cell) {
        var row = cell.closest('[data-ss-skill]');
        var lvl = levelBySlug[cell.dataset.ssLevel];
        if (row && lvl) setSkill(row.dataset.ssSkill, lvl.depth);
    }

    function activateColumn(head) {
        var domain = head.closest('[data-ss-domain]');
        var lvl = levelBySlug[head.dataset.ssCol];
        if (domain && lvl) setDomainLevel(domain, lvl.depth);
    }

    document.addEventListener('click', function (e) {
        if (modeOn) {
            var cell = e.target.closest('[data-ss-level]');
            if (cell) { activateCell(cell); return; }
            var head = e.target.closest('[data-ss-col]');
            if (head) { activateColumn(head); return; }
        }

        if (!drawer.contains(e.target)) return;

        if (e.target.closest('[data-ss-close]')) { closeDrawer(); return; }
        if (e.target.closest('[data-ss-edit]')) {
            closeDrawer(); setMode(true);
            var first = document.querySelector('.ss-row--on .cap-skill') || document.querySelector('.cap-group');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (e.target.closest('[data-ss-print]')) { window.print(); return; }

        var copy = e.target.closest('[data-ss-copy]');
        if (copy) { copyLink(); return; }

        if (!busy) {
            var jadd = e.target.closest('[data-ss-jadd]');
            if (jadd) { var j1 = targetJourney(); withBusy(setInJourney(jadd.dataset.ssJadd, true), 'Added to ' + (j1 ? j1.name : 'journey')); return; }
            var jrm = e.target.closest('[data-ss-jremove]');
            if (jrm) { var j2 = targetJourney(); withBusy(setInJourney(jrm.dataset.ssJremove, false), 'Removed from ' + (j2 ? j2.name : 'journey')); return; }
            if (e.target.closest('[data-ss-jaddall]')) {
                var jt = targetJourney();
                var missing = buildAdvice().advised.map(function (x) { return x.module.slug; })
                    .filter(function (s) { return jt && jt.slugs.indexOf(s) === -1; });
                var chain = Promise.resolve();
                missing.forEach(function (s) { chain = chain.then(function () { return setInJourney(s, true); }); });
                withBusy(chain, missing.length + ' module' + (missing.length === 1 ? '' : 's') + ' added to ' + (jt ? jt.name : 'journey'));
                return;
            }
        }
        if (e.target.closest('[data-ss-jnew]')) { showNewJourney = true; renderOverview(); var f = drawer.querySelector('[data-ss-jnew-form] input'); if (f) f.focus(); return; }
        if (e.target.closest('[data-ss-jnew-cancel]')) { showNewJourney = false; renderOverview(); return; }

        var rm = e.target.closest('[data-ss-remove]');
        if (rm) {
            setSkill(rm.dataset.ssRemove, 0);
            if (Object.keys(selection).length) renderOverview();
            else closeDrawer();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!modeOn || (e.key !== 'Enter' && e.key !== ' ')) return;
        var cell = e.target.closest && e.target.closest('[data-ss-level]');
        var head = e.target.closest && e.target.closest('[data-ss-col]');
        if (!cell && !head) return;
        e.preventDefault();
        if (cell) activateCell(cell); else activateColumn(head);
    });

    // Escape: let the module panel / journey modal (stacked on top) close first.
    window.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !drawerOpen()) return;
        var panel = document.getElementById('modulePanel');
        var modal = document.getElementById('journeyModal');
        var overlayOnTop = (panel && panel.classList.contains('open')) || (modal && modal.classList.contains('open'));
        if (!overlayOnTop) closeDrawer();
    }, true);

    // A module can also be added via the module panel's journey modal — re-sync afterwards.
    var jm = document.getElementById('journeyModalContent');
    if (jm && window.MutationObserver && JOURNEYS) {
        var syncTimer = null;
        new MutationObserver(function () {
            clearTimeout(syncTimer);
            syncTimer = setTimeout(function () {
                refreshJourneys().then(function () { if (drawerOpen()) renderOverview(); });
            }, 300);
        }).observe(jm, { childList: true });
    }

    drawer.addEventListener('change', function (e) {
        var sel = e.target.closest('[data-ss-journey]');
        if (sel) { setTargetJourney(parseInt(sel.value, 10)); renderOverview(); return; }
        var cs = e.target.closest('[data-ss-jclient]');
        if (cs) {
            var nameField = drawer.querySelector('[data-ss-jclientname]');
            if (nameField) nameField.hidden = cs.value !== '';
        }
    });

    drawer.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-ss-jnew-form]');
        if (!form) return;
        e.preventDefault();
        var fd = new FormData(form);
        var err = form.querySelector('[data-ss-jnew-err]');
        var data = { name: fd.get('name') || '', audience: fd.get('audience') || '', clientId: fd.get('clientId') || '', clientName: fd.get('clientName') || '' };
        if (!data.clientId && !String(data.clientName).trim()) {
            if (err) err.textContent = 'Choose a client or enter a new client name.';
            return;
        }
        var btn = form.querySelector('button[type=submit]');
        if (btn) btn.disabled = true;
        post('/journeys/create', data).then(function (d) {
            updateHeaderCount(d.count);
            return refreshJourneys().then(function () {
                if (d.activeId) setTargetJourney(d.activeId);
                showNewJourney = false;
                renderOverview();
                if (window.showToast) window.showToast('Journey created');
            });
        }).catch(function (ex) {
            if (err) err.textContent = ex.message || 'Could not create the journey.';
            if (btn) btn.disabled = false;
        });
    });

    window.addEventListener('hashchange', function () {
        var h = readHash();
        if (h === null) return;
        selection = parseProfile(h);
        paintAll();
    });

    function copyLink() {
        var url = shareUrl();
        var done = function () { if (window.showToast) window.showToast('Share link copied'); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done, function () { fallbackCopy(url); done(); });
        } else {
            fallbackCopy(url); done();
        }
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) { /* ignore */ }
        document.body.removeChild(ta);
    }

    /* ------------------------------------------------------------
       Helpers
       ------------------------------------------------------------ */
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function cssEscape(s) {
        if (window.CSS && window.CSS.escape) return CSS.escape(s);
        return String(s).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    /* ------------------------------------------------------------
       Init
       ------------------------------------------------------------ */
    // Overlays live at <body> level so they stack and print cleanly.
    [tray, scrim, drawer].forEach(function (el) { if (el) body.appendChild(el); });
    window.addEventListener('beforeprint', function () {
        drawer.querySelectorAll('details').forEach(function (d) { d.open = true; });
    });

    selection = load();
    var fromLink = readHash() !== null && Object.keys(selection).length > 0;
    setMode(false);
    if (fromLink) save(); // normalise the hash
    if (fromLink) openDrawer();
})();
