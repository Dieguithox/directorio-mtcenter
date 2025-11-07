// /View/contenido/js/dashboard.admin.js
document.addEventListener('DOMContentLoaded', () => {
    const $ = s => document.querySelector(s);

    const timeAgo = (tsMs) => {
        const diff = Math.max(1, Math.floor((Date.now() - tsMs) / 1000));
        const m = Math.floor(diff / 60), h = Math.floor(m / 60), d = Math.floor(h / 24);
        if (diff < 60) return `Hace ${diff} seg.`;
        if (m < 60) return `Hace ${m} min.`;
        if (h < 24) return `Hace ${h} horas.`;
        return `Hace ${d} días.`;
    };

    function setKpis(s) {
        $('#kpi-total-contactos .adm-kpi-value').textContent = s.total_contactos ?? 0;
        $('#kpi-usuarios        .adm-kpi-value').textContent = s.usuarios ?? 0;
        $('#kpi-extensiones     .adm-kpi-value').textContent = s.extensiones ?? 0;
        $('#kpi-deptos          .adm-kpi-value').textContent =
            Array.isArray(s.departamentos) ? s.departamentos.length : 0;
    }

    let chart;

    function renderChart(deptos) {
        const canvas = document.getElementById('deptosChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const labels = Array.isArray(deptos) ? deptos.map(d => d.name) : [];
        const data = Array.isArray(deptos) ? deptos.map(d => Number(d.value) || 0) : [];
        const total = data.reduce((a, b) => a + b, 0);
        const palette = ['#6b8af7', '#c084fc', '#fbbf24', '#34d399', '#f87171', '#60a5fa', '#fb7185', '#22d3ee'];

        if (chart) chart.destroy();
        chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: labels.map((_, i) => palette[i % palette.length]),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => {
                                const val = c.parsed || 0;
                                const pct = total ? (val * 100 / total).toFixed(1) : '0.0';
                                return `${c.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });

        const legendHtml = labels.length
            ? labels.map((l, i) =>
                `<span class="adm-legend-item">
            <span class="adm-dot" style="--c:${chart.data.datasets[0].backgroundColor[i]}"></span>${l}
            </span>`).join('')
            : '<span class="text-muted">Sin datos</span>';

        const legendEl = document.getElementById('chart-legend');
        if (legendEl) legendEl.innerHTML = legendHtml;
    }

    let ACT = Array.isArray(window.ACTIVITY) ? window.ACTIVITY.slice() : [];

    function renderActivity() {
        const ul = document.getElementById('activityList');
        if (!ul) return;
        if (!ACT.length) {
            ul.innerHTML = `<li class="adm-empty"><i class="bi bi-inbox"></i> Sin actividad aún</li>`;
            return;
        }
        ul.innerHTML = ACT.map(a => `
        <li class="adm-activity-item">
            <div class="adm-activity-text">${a.text}</div>
            <div class="adm-activity-time" data-ts="${a.ts}">${timeAgo(a.ts)}</div>
        </li>
    `).join('');
    }

    function addActivity(text, tsMs = Date.now()) {
        ACT.unshift({ text, ts: tsMs });
        ACT = ACT.slice(0, 50);
        renderActivity();
    }

    setInterval(() => {
        document.querySelectorAll('.adm-activity-time').forEach(el => {
            const ts = Number(el.getAttribute('data-ts'));
            el.textContent = timeAgo(ts);
        });
    }, 30000);

    document.getElementById('btn-clear-activity')?.addEventListener('click', () => {
        ACT = [];
        renderActivity();
    });
    document.getElementById('btn-reload-activity')?.addEventListener('click', () => {
        location.reload();
    });

    // Init con las globales inyectadas por PHP
    setKpis(window.SUMMARY || { total_contactos: 0, usuarios: 0, extensiones: 0, departamentos: [] });
    renderChart((window.SUMMARY && window.SUMMARY.departamentos) || []);
    renderActivity();

    // API opcional igual que antes
    window.AdminPanel = {
        setSummary: (s) => { setKpis(s); renderChart(s.departamentos || []); },
        pushActivity: (t, ts) => addActivity(t, ts)
    };
});