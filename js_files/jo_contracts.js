
(function () {
    if (document.getElementById('joPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'joPopupStyle';
    s.textContent =
        '@keyframes joFadeIn  { from { opacity: 0; transform: scale(0.96); }' +
                               ' to   { opacity: 1; transform: scale(1);    } }' +
        '@keyframes joFadeOut { from { opacity: 1; } to { opacity: 0; } }';
    document.head.appendChild(s);
})();

function showPopup(message, type) {
    const existing = document.getElementById('joPopupOverlay');
    if (existing) existing.remove();

    const icons = { warning: '', info: 'ℹ', success: '' };
    const icon  = icons[type] || 'ℹ';

    const overlay = document.createElement('div');
    overlay.id = 'joPopupOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:400px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;' +
        'animation:joFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2.2rem;margin-bottom:10px;">' + icon + '</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:22px;">' +
            message +
        '</p>' +
        '<button id="joPopupOkBtn"' +
        ' style="padding:9px 30px;background:#2a5c30;color:#fff;border:none;border-radius:8px;' +
        'font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;font-weight:700;' +
        'text-transform:uppercase;cursor:pointer;letter-spacing:0.3px;">OK</button>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    /* Close handlers */
    document.getElementById('joPopupOkBtn').addEventListener('click', function () {
        overlay.remove();
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
}

function previewFile(btn) {
    const row       = btn.closest('tr');
    const refFolder = (row.dataset.refFolder || '').trim();

    if (refFolder === '') {
        showPopup('No reference folder found for this record.', 'warning');
        return;
    }

    /* Build the PDF path from the ref_folder name */
    const pdfPath = 'JO_Contract_files/' + refFolder + '.pdf';

    /* Open the PDF in a new tab */
    window.open(pdfPath, '_blank');
}

function noFileAlert(btn) {
    const row       = btn.closest('tr');
    const name      = (row.dataset.name      || 'this record').toUpperCase();
    const refFolder = (row.dataset.refFolder || '').toUpperCase();

    showPopup(
        'No PDF file is available for<br>' +
        '<strong>' + name + '</strong>' +
        (refFolder
            ? '<br><span style="font-size:0.82rem;color:#7a9e7e;">Reference Folder: ' + refFolder + '</span>'
            : '') +
        '.',
        'info'
    );
}

(function () {
    const searchInput = document.getElementById('joSearch');
    const tableBody   = document.getElementById('joTableBody');
    const dataRows    = tableBody
        ? Array.from(tableBody.querySelectorAll('tr.jo-data-row'))
        : [];

    if (!searchInput || !dataRows.length) return;

    /* ── Reapply green/white stripe only on visible rows ── */
    function restripe() {
        let idx = 0;
        dataRows.forEach(function (row) {
            if (row.style.display === 'none') return;
            row.classList.remove('row-odd', 'row-even');
            row.classList.add(idx % 2 === 0 ? 'row-odd' : 'row-even');
            idx++;
        });
    }

    /* Initial stripe on page load */
    restripe();

    /* ── Filter on every keystroke ── */
    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visible = 0;

        dataRows.forEach(function (row) {
            const name      = (row.dataset.name      || '').toLowerCase();
            const refFolder = (row.dataset.refFolder || '').toLowerCase();
            const match     = !query || name.includes(query) || refFolder.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        restripe();

        /* No-results feedback row */
        let noRow = tableBody.querySelector('tr.no-results-live');
        if (visible === 0 && query) {
            if (!noRow) {
                noRow = document.createElement('tr');
                noRow.className = 'no-results-live';
                noRow.innerHTML =
                    '<td colspan="10" style="text-align:center;padding:20px;' +
                    'color:#7a9e7e;font-style:italic;">' +
                    'No records match &ldquo;' + escHtml(query) + '&rdquo;.</td>';
                tableBody.insertBefore(noRow, tableBody.firstChild);
            } else {
                noRow.style.display = '';
                noRow.querySelector('td').innerHTML =
                    'No records match &ldquo;' + escHtml(query) + '&rdquo;.';
            }
        } else if (noRow) {
            noRow.style.display = 'none';
        }
    });

    /* ── HTML escape helper ── */
    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})();