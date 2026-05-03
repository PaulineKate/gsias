(function () {
    const searchInput = document.getElementById('logsSearch');
    const tableBody   = document.getElementById('logsTableBody');
    const dataRows    = tableBody
        ? Array.from(tableBody.querySelectorAll('tr.logs-data-row'))
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

    /* ── Filter rows based on search query ── */
    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visible = 0;

        dataRows.forEach(function (row) {
            const name   = (row.dataset.name   || '').toLowerCase();
            const gmail  = (row.dataset.gmail  || '').toLowerCase();
            const id     = (row.dataset.id     || '').toLowerCase();

            if (!query) {
                row.style.display = '';
                visible++;
            } else {
                const matches = name.startsWith(query)
                             || gmail.includes(query)
                             || id.includes(query);

                if (matches) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        restripe();

        /* No-results feedback row */
        let noRow = tableBody.querySelector('tr.no-results-live');
        if (visible === 0 && query) {
            if (!noRow) {
                noRow = document.createElement('tr');
                noRow.className = 'no-results-live';
                noRow.innerHTML =
                    '<td colspan="5" style="text-align:center;padding:20px;' +
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