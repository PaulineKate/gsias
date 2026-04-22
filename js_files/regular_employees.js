(function () {
    const searchInput = document.getElementById('regSearch');
    const tableBody   = document.getElementById('regTableBody');
    const dataRows    = tableBody
        ? Array.from(tableBody.querySelectorAll('tr.reg-data-row'))
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
            const name = (row.dataset.name || '').toLowerCase();
            const id   = (row.dataset.id   || '').toLowerCase();
            const match = !query || name.includes(query) || id.includes(query);
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
                    '<td colspan="8" style="text-align:center;padding:20px;' +
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