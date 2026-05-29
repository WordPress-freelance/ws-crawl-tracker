/* global wsctData, Chart */
(function () {
    'use strict';

    var d = window.wsctData || {};
    var charts = {};

    function t(key) {
        return (d.i18n && d.i18n[key]) ? d.i18n[key] : key;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str == null ? '' : str)));
        return div.innerHTML;
    }

    function num(n) {
        return (parseInt(n, 10) || 0).toLocaleString();
    }

    function codeClass(code) {
        code = parseInt(code, 10) || 0;
        if (code >= 500) return 'err';
        if (code >= 400) return 'err';
        if (code >= 300) return 'warn';
        if (code >= 200) return 'ok';
        return 'warn';
    }

    var PALETTE = ['#7C5CBF', '#A899D4', '#6BC98A', '#E8A93A', '#9B8EC4', '#E05C5C', '#5B4D9C', '#C4BFDA', '#6E5FC0', '#463A78'];

    // ── Rendu KPIs ───────────────────────────────────────────────────────────
    function renderKpis(stats) {
        document.querySelectorAll('[data-kpi]').forEach(function (el) {
            var key = el.getAttribute('data-kpi');
            el.textContent = num(stats[key]);
        });
    }

    // ── Recommandations ────────────────────────────────────────────────────────
    function renderRecos(recos) {
        var wrap = document.getElementById('wsct-recos');
        if (!wrap) return;
        if (!recos || !recos.length) {
            wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('noData')) + '</p>';
            return;
        }
        var icons = { ok: '✓', warn: '!', err: '×' };
        wrap.innerHTML = recos.map(function (r) {
            var lvl = (r.level === 'ok' || r.level === 'warn' || r.level === 'err') ? r.level : 'warn';
            return '<div class="wsct-reco wsct-reco--' + lvl + '">' +
                '<span class="wsct-reco__icon">' + icons[lvl] + '</span>' +
                '<div class="wsct-reco__body">' +
                '<p class="wsct-reco__title">' + escHtml(r.title) + '</p>' +
                '<p class="wsct-reco__detail">' + escHtml(r.detail) + '</p>' +
                '</div></div>';
        }).join('');
    }

    // ── Charts ───────────────────────────────────────────────────────────────
    function destroyChart(key) {
        if (charts[key]) { charts[key].destroy(); delete charts[key]; }
    }

    var GRID = 'rgba(74,66,96,.25)';
    var TICK = '#9590A8';

    function baseOpts(extra) {
        var o = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: GRID }, ticks: { color: TICK, font: { size: 10 } } },
                y: { grid: { color: GRID }, ticks: { color: TICK, font: { size: 10 } }, beginAtZero: true }
            }
        };
        return Object.assign(o, extra || {});
    }

    function renderTimeline(rows) {
        var el = document.getElementById('wsct-chart-timeline');
        if (!el) return;
        destroyChart('timeline');
        var labels = rows.map(function (r) { return r.day; });
        var data = rows.map(function (r) { return parseInt(r.hits, 10) || 0; });
        charts.timeline = new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: t('hits'), data: data,
                    borderColor: '#7C5CBF', backgroundColor: 'rgba(124,92,191,.15)',
                    fill: true, tension: .3, pointRadius: 2, pointBackgroundColor: '#A899D4', borderWidth: 2
                }]
            },
            options: baseOpts()
        });
    }

    function renderBots(rows) {
        var el = document.getElementById('wsct-chart-bots');
        if (!el) return;
        destroyChart('bots');
        var labels = rows.map(function (r) { return r.bot_name; });
        var data = rows.map(function (r) { return parseInt(r.hits, 10) || 0; });
        charts.bots = new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: PALETTE, borderColor: '#14121C', borderWidth: 2 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'right', labels: { color: '#C4BFDA', font: { size: 11 }, padding: 10, boxWidth: 12 } } }
            }
        });
    }

    function renderHourly(rows) {
        var el = document.getElementById('wsct-chart-hourly');
        if (!el) return;
        destroyChart('hourly');
        var byHour = {};
        rows.forEach(function (r) { byHour[parseInt(r.hour, 10)] = parseInt(r.hits, 10) || 0; });
        var labels = [], data = [];
        for (var h = 0; h < 24; h++) { labels.push(h + 'h'); data.push(byHour[h] || 0); }
        charts.hourly = new Chart(el, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: 'rgba(124,92,191,.55)', borderColor: '#7C5CBF', borderWidth: 1, borderRadius: 3 }] },
            options: baseOpts()
        });
    }

    // ── Status breakdown ───────────────────────────────────────────────────────
    function renderStatus(rows) {
        var wrap = document.getElementById('wsct-status');
        if (!wrap) return;
        if (!rows || !rows.length) { wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('noData')) + '</p>'; return; }
        wrap.innerHTML = rows.map(function (r) {
            var code = parseInt(r.status_code, 10) || 0;
            var fam = Math.floor(code / 100);
            return '<div class="wsct-status wsct-status--' + fam + '">' +
                '<span class="wsct-status__code">' + escHtml(code || '—') + '</span>' +
                '<span class="wsct-status__hits">' + num(r.hits) + ' ' + escHtml(t('hits').toLowerCase()) + '</span>' +
                '</div>';
        }).join('');
    }

    // ── Heatmap pages ──────────────────────────────────────────────────────────
    function renderHeatmap(rows) {
        var wrap = document.getElementById('wsct-heatmap');
        if (!wrap) return;
        if (!rows || !rows.length) { wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('noData')) + '</p>'; return; }
        var max = rows.reduce(function (m, r) { return Math.max(m, parseInt(r.hits, 10) || 0); }, 0) || 1;
        wrap.innerHTML = rows.map(function (r) {
            var hits = parseInt(r.hits, 10) || 0;
            var pct = Math.round(hits / max * 100);
            var path = '';
            try { path = new URL(r.url).pathname || r.url; } catch (e) { path = r.url; }
            var cc = codeClass(r.last_status);
            return '<div class="wsct-hm-row">' +
                '<span class="wsct-hm-bar" style="width:' + pct + '%"></span>' +
                '<span class="wsct-hm-url" title="' + escHtml(r.url) + '">' + escHtml(path) + '</span>' +
                '<span class="wsct-hm-hits">' + num(hits) + '</span>' +
                '<span class="wsct-hm-status wsct-code--' + cc + '">' + escHtml(r.last_status) + '</span>' +
                '</div>';
        }).join('');
    }

    // ── Sessions + chemin de crawl ───────────────────────────────────────────────
    function renderSessions(rows) {
        var sel = document.getElementById('wsct-session');
        if (!sel) return;
        if (!rows || !rows.length) {
            sel.innerHTML = '<option value="">' + escHtml(t('noData')) + '</option>';
            document.getElementById('wsct-path').innerHTML = '';
            return;
        }
        sel.innerHTML = rows.map(function (s) {
            var label = s.bot_name + ' · ' + num(s.hits) + ' ' + t('hits').toLowerCase() + ' · ' + s.started;
            return '<option value="' + escHtml(s.session_id) + '">' + escHtml(label) + '</option>';
        }).join('');
        loadSessionPath(rows[0].session_id);
    }

    function loadSessionPath(sessionId) {
        var wrap = document.getElementById('wsct-path');
        if (!wrap || !sessionId) return;
        wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('loading')) + '</p>';

        var form = new FormData();
        form.append('action', 'wsct_get_session');
        form.append('nonce', d.nonce || '');
        form.append('session_id', sessionId);

        fetch(d.ajaxUrl, { method: 'POST', body: form })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success || !res.data.path || !res.data.path.length) {
                    wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('noData')) + '</p>';
                    return;
                }
                wrap.innerHTML = res.data.path.map(function (step) {
                    var cc = codeClass(step.status_code);
                    var path = '';
                    try { path = new URL(step.url).pathname || step.url; } catch (e) { path = step.url; }
                    return '<div class="wsct-step">' +
                        '<div class="wsct-step__rail"><span class="wsct-step__dot"></span><span class="wsct-step__line"></span></div>' +
                        '<div class="wsct-step__body">' +
                        '<div class="wsct-step__url" title="' + escHtml(step.url) + '">' + escHtml(path) + '</div>' +
                        '<div class="wsct-step__meta">' +
                        '<span class="wsct-step__code wsct-step__code--' + cc + '">' + escHtml(step.status_code) + '</span>' +
                        '<span>' + escHtml(step.hit_time) + '</span>' +
                        '</div></div></div>';
                }).join('');
            })
            .catch(function () { wrap.innerHTML = '<p class="wsct-empty">' + escHtml(t('error')) + '</p>'; });
    }

    // ── Flux récent ────────────────────────────────────────────────────────────
    function renderRecent(rows) {
        var body = document.getElementById('wsct-recent');
        if (!body) return;
        if (!rows || !rows.length) {
            body.innerHTML = '<tr><td colspan="5" class="wsct-empty">' + escHtml(t('noData')) + '</td></tr>';
            return;
        }
        body.innerHTML = rows.map(function (r) {
            var cc = codeClass(r.status_code);
            var path = '';
            try { path = new URL(r.url).pathname || r.url; } catch (e) { path = r.url; }
            var verified = parseInt(r.is_verified, 10) === 1
                ? '<span class="wsct-verified">✓</span>'
                : '<span class="wsct-unverified">—</span>';
            return '<tr>' +
                '<td>' + escHtml(r.hit_time) + '</td>' +
                '<td><span class="wsct-bot-tag">' + escHtml(r.bot_name) + '</span></td>' +
                '<td class="wsct-td-url" title="' + escHtml(r.url) + '">' + escHtml(path) + '</td>' +
                '<td><span class="wsct-code wsct-code--' + cc + '">' + escHtml(r.status_code) + '</span></td>' +
                '<td>' + verified + '</td>' +
                '</tr>';
        }).join('');
    }

    // ── Chargement global ──────────────────────────────────────────────────────
    function load() {
        var loading = document.getElementById('wsct-loading');
        if (loading) loading.style.display = 'flex';

        var days = (document.getElementById('wsct-days') || {}).value || '30';
        var bot = (document.getElementById('wsct-bot') || {}).value || '';

        var form = new FormData();
        form.append('action', 'wsct_get_data');
        form.append('nonce', d.nonce || '');
        form.append('days', days);
        form.append('bot', bot);

        fetch(d.ajaxUrl, { method: 'POST', body: form })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (loading) loading.style.display = 'none';
                if (!res.success) return;
                var x = res.data;
                renderKpis(x.stats || {});
                renderRecos(x.recommendations || []);
                renderTimeline(x.timeline || []);
                renderBots(x.by_bot || []);
                renderHourly(x.hourly || []);
                renderStatus(x.status_breakdown || []);
                renderHeatmap(x.top_pages || []);
                renderSessions(x.sessions || []);
                renderRecent(x.recent || []);
            })
            .catch(function () { if (loading) loading.style.display = 'none'; });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('wsct-kpis')) return; // pas sur le dashboard

        var refresh = document.getElementById('wsct-refresh');
        if (refresh) refresh.addEventListener('click', load);

        ['wsct-days', 'wsct-bot'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', load);
        });

        var sessSel = document.getElementById('wsct-session');
        if (sessSel) sessSel.addEventListener('change', function () { loadSessionPath(this.value); });

        load();
    });
}());
