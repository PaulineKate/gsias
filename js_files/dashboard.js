function renderBars(key) {
    const rows = employeeData[key];

    if (!rows || rows.length === 0) {
        document.getElementById('bar-labels').innerHTML = '';
        document.getElementById('bars').innerHTML = '<div style="font-size:12px;color:#888;padding:8px 0;">No data available.</div>';
        return;
    }

    const max = Math.max(...rows.map(r => r.count));

    document.getElementById('bar-labels').innerHTML = rows
        .map(r => `<div class="chart-label">${r.role}</div>`)
        .join('');

    document.getElementById('bars').innerHTML = rows
        .map((r, i) => `
            <div class="bar-row">
                <div class="bar-fill${i % 2 === 1 ? ' light' : ''}" style="width:${Math.round((r.count / max) * 100)}%"></div>
                <div class="bar-val">${r.count}</div>
            </div>`)
        .join('');
}

function setTab(btn, key) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    renderBars(key);
}