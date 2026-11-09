document.addEventListener('DOMContentLoaded', function () {

    // ---------- Read colors from the CSS variables in analytics.css ----------
    const root = getComputedStyle(document.querySelector('.analytics'));
    const v = (name) => root.getPropertyValue(name).trim();

    const deepTeal  = v('--an-deep-teal');
    const cyan      = v('--an-cyan');
    const sky       = v('--an-sky');
    const coral     = v('--an-coral');
    const coralSoft = v('--an-coral-soft');
    const sand      = v('--an-sand');
    const ink       = v('--an-ink');
    const muted     = v('--an-muted');
    const grid      = v('--an-grid');

    // ---------- Global defaults ----------
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = muted;

    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 14;

    Chart.defaults.plugins.tooltip.backgroundColor = deepTeal;
    Chart.defaults.plugins.tooltip.cornerRadius = 14;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.titleColor = '#fff';
    Chart.defaults.plugins.tooltip.bodyColor = '#fff';
    Chart.defaults.plugins.tooltip.boxPadding = 6;
    Chart.defaults.plugins.tooltip.usePointStyle = true;

    // Soft grid, no hard axis lines
    const softScales = {
        x: {
            grid: { display: false },
            border: { display: false }
        },
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: grid },
            border: { display: false }
        }
    };

    // Shortens long labels so they never get cut off
    const shorten = (label, max) => label.length > max ? label.slice(0, max) + '…' : label;

    // ---------- Data from Laravel (set in analytics.blade.php) ----------
    const {
        monthLabels,
        monthData,
        statusData,
        categoryLabels,
        categoryData,
        memberLabels,
        memberData,
        weekdayLabels,
        weekdayData
    } = window.analyticsData;

    // ---------- 1. Borrowings over time (smooth line) ----------
    const monthCtx = document.getElementById('monthChart').getContext('2d');

    const gradient = monthCtx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(13, 200, 239, .35)');   // fades from --an-cyan
    gradient.addColorStop(1, 'rgba(13, 200, 239, 0)');

    new Chart(monthCtx, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Borrowings',
                data: monthData,
                borderColor: cyan,
                backgroundColor: gradient,
                borderWidth: 4,
                borderCapStyle: 'round',
                borderJoinStyle: 'round',
                fill: true,
                tension: 0.45,
                pointRadius: 6,
                pointHoverRadius: 9,
                pointBackgroundColor: '#fff',
                pointBorderColor: cyan,
                pointBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: softScales
        }
    });

    // ---------- 2. Borrowing status (rounded doughnut) ----------
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Borrowed', 'Returned', 'Overdue'],
            datasets: [{
                data: statusData,
                backgroundColor: [deepTeal, cyan, coral],
                borderWidth: 0,
                borderRadius: 14,
                spacing: 5,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // ---------- 3. Top borrowers (pill-shaped horizontal bars) ----------
    const memberCanvas = document.getElementById('memberChart');

    if (memberCanvas) {
        new Chart(memberCanvas, {
            type: 'bar',
            data: {
                labels: memberLabels,
                datasets: [{
                    label: 'Books borrowed',
                    data: memberData,
                    backgroundColor: [deepTeal, cyan, sky, sand, coralSoft],
                    borderRadius: 999,
                    borderSkipped: false,
                    barThickness: 24
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: grid },
                        border: { display: false }
                    },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: ink,
                            callback: function (value) {
                                return shorten(this.getLabelForValue(value), 22);
                            }
                        }
                    }
                }
            }
        });
    }

    // ---------- 4. Books by category (rounded doughnut, legend at the bottom) ----------
    const categoryCanvas = document.getElementById('categoryChart');

    if (categoryCanvas) {
        // 6 categories + "Others" (muted gray)
        const palette = [deepTeal, cyan, coral, sky, sand, coralSoft, muted];

        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: categoryLabels.map((_, i) => palette[i % palette.length]),
                    borderWidth: 0,
                    borderRadius: 12,
                    spacing: 4,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            generateLabels: function (chart) {
                                const base = Chart.overrides.doughnut.plugins.legend.labels.generateLabels(chart);
                                base.forEach(function (item) {
                                    item.text = shorten(item.text, 16);
                                });
                                return base;
                            }
                        }
                    }
                }
            }
        });
    }

    // ---------- 5. Busiest days (rounded bars) ----------
    const maxDay = Math.max.apply(null, weekdayData);

    new Chart(document.getElementById('weekdayChart'), {
        type: 'bar',
        data: {
            labels: weekdayLabels,
            datasets: [{
                label: 'Borrowings',
                data: weekdayData,
                // the busiest day is highlighted in deep teal
                backgroundColor: weekdayData.map(function (n) {
                    return (n === maxDay && maxDay > 0) ? deepTeal : sky;
                }),
                borderRadius: 14,
                borderSkipped: false,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: softScales
        }
    });

    // ---------- 6. Book covers for Most Popular Books (Open Library) ----------
    const coverCache = new Map();

    const norm = (s) => (s || '').toString().toLowerCase()
        .normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\p{L}\p{N}\s]/gu, '').replace(/\s+/g, ' ').trim();

    async function findCover(title, author) {
        const t = norm(title), a = norm(author);
        if (!t) return null;

        const key = t + '|' + a;
        if (coverCache.has(key)) return coverCache.get(key);

        let url = null;
        try {
            const params = new URLSearchParams({
                title, limit: '10', fields: 'title,author_name,cover_i'
            });
            if (author) params.set('author', author);

            const res = await fetch('https://openlibrary.org/search.json?' + params);
            if (res.ok) {
                const docs = ((await res.json()).docs) || [];
                const match = docs.find(function (doc) {
                    const dt = norm(doc.title);
                    if (!doc.cover_i) return false;
                    if (dt !== t && !t.includes(dt) && !dt.includes(t)) return false;
                    if (!a) return true;
                    return (doc.author_name || []).map(norm)
                        .some(function (x) { return x === a || x.includes(a) || a.includes(x); });
                });
                if (match) {
                    url = 'https://covers.openlibrary.org/b/id/' + match.cover_i + '-S.jpg?default=false';
                }
            }
        } catch (e) { /* network error: keep the icon */ }

        coverCache.set(key, url);
        return url;
    }

    document.querySelectorAll('.rank-cover').forEach(async function (box) {
        const url = await findCover(box.dataset.title, box.dataset.author);
        if (!url) return;

        const img = box.querySelector('img');
        const icon = box.querySelector('i');
        img.onload = function () {
            img.style.display = 'block';
            icon.style.display = 'none';
        };
        img.src = url;
    });

});