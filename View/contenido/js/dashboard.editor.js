// /View/contenido/js/dashboard.editor.js
// (no module) — incluye utilidades, render de KPIs, gráfica, actividad y cola

(function () {
    // ------- Helpers -------
    function $(sel) { return document.querySelector(sel); }

    function timeAgo(tsMs) {
        const diff = Math.max(1, Math.floor((Date.now() - tsMs) / 1000));
        const m = Math.floor(diff / 60), h = Math.floor(m / 60), d = Math.floor(h / 24);
        if (diff < 60) return `Hace ${diff} seg.`;
        if (m < 60) return `Hace ${m} min.`;
        if (h < 24) return `Hace ${h} horas.`;
        return `Hace ${d} días.`;
    }

    function makeDoughnut(ctx, labels, data, colors, cutout) {
        const total = data.reduce((a, b) => a + b, 0);
        return new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 1 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: cutout || '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => {
                                const v = c.parsed || 0;
                                const pct = total ? (v * 100 / total).toFixed(1) : '0.0';
                                return `${c.label}: ${v} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function legendHtml(labels, colors, cls) {
        const klass = cls || 'ed-legend__item';
        return labels.map((l, i) =>
            `<span class="${klass}"><span class="ed-dot" style="--c:${colors[i]}"></span>${l}</span>`
        ).join('');
    }

    function renderActivity(selectorUl, items) {
        const ul = $(selectorUl);
        if (!ul) return;
        if (!Array.isArray(items) || !items.length) {
            ul.innerHTML = `<li class="ed-empty"><i class="bi bi-inbox"></i> Sin actividad</li>`;
            return;
        }
        ul.innerHTML = items.map(a => {
            const ts = a.ts || Date.now();
            return `
        <li class="ed-activity__item">
          <div class="ed-activity__text">${a.text || ''}</div>
          <div class="ed-activity__time" data-ts="${ts}">${timeAgo(ts)}</div>
        </li>
      `;
        }).join('');
    }

    function startAgoTicker(timeSelector) {
        const sel = timeSelector || '.ed-activity__time';
        setInterval(() => {
            document.querySelectorAll(sel).forEach(el => {
                const ts = Number(el.getAttribute('data-ts'));
                if (!isNaN(ts)) el.textContent = timeAgo(ts);
            });
        }, 30000);
    }

    // ------- Editor-specific -------
    let chartEstado;

    function setKpisEditor(s) {
        $('#kpi-publicados  .ed-kpi__value') && ($('#kpi-publicados  .ed-kpi__value').textContent = s.publicados ?? 0);
        $('#kpi-revision    .ed-kpi__value') && ($('#kpi-revision    .ed-kpi__value').textContent = s.revision ?? 0);
        $('#kpi-borradores  .ed-kpi__value') && ($('#kpi-borradores  .ed-kpi__value').textContent = s.borradores ?? 0);
        $('#kpi-programados .ed-kpi__value') && ($('#kpi-programados .ed-kpi__value').textContent = s.programados ?? 0);
    }

    function renderChartEstado(estados) {
        const canvas = document.getElementById('estadoChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (chartEstado) chartEstado.destroy();

        const labels = (estados || []).map(e => e.name);
        const data = (estados || []).map(e => Number(e.value) || 0);

        // Mantén tu paleta principal si viene desde PHP; si falta color, aplica fallback ordenado
        const fallback = ['#fba1f2ff', '#ecfd8aff', '#d9b4fdff', '#a2ffa0ff', '#57beff', '#ffc380', '#9e41fb', '#16d4ae'];
        const colors = (estados || []).map((e, i) => e.color || fallback[i % fallback.length]);

        chartEstado = makeDoughnut(ctx, labels, data, colors, '62%');

        const legend = document.getElementById('chart-legend-estado');
        if (legend) {
            legend.innerHTML = labels.length ? legendHtml(labels, colors, 'ed-legend__item') : '<span class="ed-muted">Sin datos</span>';
        }
    }

    function renderQueue(list) {
        const ul = $('#queueList');
        if (!ul) return;
        if (!Array.isArray(list) || !list.length) {
            ul.innerHTML = `<li class="ed-empty"><i class="bi bi-inbox"></i> Sin solicitudes</li>`;
            return;
        }
        ul.innerHTML = list.map(q => {
            const ts = q.ts || Date.now();
            return `
            <li class="ed-queue__item">
            <div class="ed-queue__main">
                <div class="ed-queue__name">${q.name || '—'}</div>
                <div class="ed-queue__desc">${q.change || ''}</div>
            </div>
            <div class="ed-queue__side">
                <span class="ed-badge" data-ts="${ts}">${timeAgo(ts)}</span>
                <div class="ed-queue__actions">
                <button class="ed-btn ed-btn--ok" title="Aprobar"><i class="bi bi-check2"></i></button>
                <button class="ed-btn ed-btn--warn" title="Rechazar"><i class="bi bi-x"></i></button>
                <a class="ed-btn" title="Ver ficha" href="${(window.BASE_URL || '')}/View/editor/ficha.php"><i class="bi bi-eye"></i></a>
                </div>
            </div>
            </li>
    `;
        }).join('');
    }

    function init() {
        // Lee los datos inyectados por PHP
        const data = window.EDITOR_SUMMARY || { publicados: 0, revision: 0, borradores: 0, programados: 0, estados: [] };
        const act = Array.isArray(window.EDITOR_ACTIVITY) ? window.EDITOR_ACTIVITY.slice(0, 20) : [];
        const queue = window.EDITOR_QUEUE || [];

        // KPIs + gráfica
        setKpisEditor(data);
        renderChartEstado(data.estados || []);

        // Actividad
        renderActivity('#activityList', act);
        startAgoTicker('.ed-activity__time');

        // Cola de cambios
        renderQueue(queue);

        // Botones
        document.getElementById('btn-reload-activity')?.addEventListener('click', () => {
            // Si luego tienes endpoint de recarga, haz fetch aquí.
            renderActivity('#activityList', act);
        });
        document.getElementById('btn-clear-activity')?.addEventListener('click', () => {
            renderActivity('#activityList', []);
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    document.addEventListener('DOMContentLoaded', () => {
        const qList = document.getElementById('queueList');
        const items = window.EDITOR_QUEUE || [];

        if (!qList) return;

        if (!items.length) {
            qList.innerHTML = '<li class="ed-queue__empty">No hay solicitudes de cambio.</li>';
            return;
        }

        qList.innerHTML = items.map(item => `
            <li class="ed-queue__item">
                <div class="ed-queue__title">${esc(item.prop)}</div>
                <div class="ed-queue__desc">
                    Campo: <strong>${esc(item.campo)}</strong>
                    ${item.nuevo ? ` → "${esc(item.nuevo)}"` : ''}
                </div>
                <div class="ed-queue__meta">
                    ${badge(item.estado)}
                    <span class="ed-queue__small">Por ${esc(item.solicita || 'Usuario')}</span>
                    <span class="ed-queue__small">${item.ts || ''}</span>
                </div>
            </li>
        `).join('');

        function esc(s) {
            return String(s ?? '')
                .replace(/&/g,'&amp;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        }
        function badge(est) {
            switch (est) {
                case 'aprobado': return '<span class="badge bg-success me-1">Aprobado</span>';
                case 'rechazado': return '<span class="badge bg-danger me-1">Rechazado</span>';
                default: return '<span class="badge bg-warning text-dark me-1">Pendiente</span>';
            }
        }
    });
})();