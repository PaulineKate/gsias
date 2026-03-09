
(function () {

    const veYear       = document.getElementById('veYear');
    const veNameSearch = document.getElementById('veNameSearch');
    const veNameHidden = document.getElementById('veNameHidden');
    const veNameGroup  = document.getElementById('veNameGroup');
    const veDropdown   = document.getElementById('veNameDropdown');
    const resultsArea  = document.getElementById('resultsArea');
    const noData       = document.getElementById('noData');

    let allNames = [];

    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function openDropdown()  { veDropdown.style.display = 'block'; }
    function closeDropdown() { veDropdown.style.display = 'none';  }

    function renderDropdown(filter) {
        const q = (filter || '').toLowerCase().trim();
        const matches = q
            ? allNames.filter(n => n.toLowerCase().includes(q))
            : allNames;

        veDropdown.innerHTML = '';

        if (matches.length === 0) {
            const li = document.createElement('li');
            li.className   = 've-dd-empty';
            li.textContent = 'No matches found';
            veDropdown.appendChild(li);
        } else {
            matches.forEach(name => {
                const li = document.createElement('li');
                li.className = 've-dd-item';

                if (q) {
                    const idx = name.toLowerCase().indexOf(q);
                    if (idx !== -1) {
                        li.innerHTML =
                            escHtml(name.slice(0, idx)) +
                            `<mark>${escHtml(name.slice(idx, idx + q.length))}</mark>` +
                            escHtml(name.slice(idx + q.length));
                    } else {
                        li.textContent = name;
                    }
                } else {
                    li.textContent = name;
                }

                li.addEventListener('mousedown', e => {
                    e.preventDefault();
                    selectName(name);
                });

                veDropdown.appendChild(li);
            });
        }

        openDropdown();
    }

    function selectName(name) {
        veNameHidden.value = name;
        veNameSearch.value = name;
        closeDropdown();
        loadDetails(name);
    }

    /* ── Keyboard navigation ── */
    veNameSearch.addEventListener('keydown', function (e) {
        const items  = veDropdown.querySelectorAll('.ve-dd-item');
        const active = veDropdown.querySelector('.ve-dd-item.active');
        let idx = Array.from(items).indexOf(active);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (veDropdown.style.display === 'none') renderDropdown(veNameSearch.value);
            idx = Math.min(idx + 1, items.length - 1);
            items.forEach(i => i.classList.remove('active'));
            if (items[idx]) { items[idx].classList.add('active'); items[idx].scrollIntoView({ block: 'nearest' }); }

        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = Math.max(idx - 1, 0);
            items.forEach(i => i.classList.remove('active'));
            if (items[idx]) { items[idx].classList.add('active'); items[idx].scrollIntoView({ block: 'nearest' }); }

        } else if (e.key === 'Enter' || e.key === 'Tab') {
            const highlighted = veDropdown.querySelector('.ve-dd-item.active')
                             || veDropdown.querySelector('.ve-dd-item');
            if (highlighted && veDropdown.style.display !== 'none') {
                e.preventDefault();
                selectName(highlighted.textContent.trim());
            }

        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    veNameSearch.addEventListener('input', function () {
        veNameHidden.value        = '';
        resultsArea.style.display = 'none';
        noData.style.display      = 'none';
        if (!allNames.length) return;
        renderDropdown(this.value);
    });

    veNameSearch.addEventListener('focus', function () {
        if (allNames.length) renderDropdown(this.value);
    });

    veNameSearch.addEventListener('blur', function () {
        setTimeout(closeDropdown, 150);
    });

    function resetNameWidget(placeholder) {
        allNames                        = [];
        veNameHidden.value              = '';
        veNameSearch.value              = '';
        veNameSearch.placeholder        = placeholder || '— Select a Year First —';
        veNameSearch.disabled           = true;
        veNameGroup.style.opacity       = '0.4';
        veNameGroup.style.pointerEvents = 'none';
        closeDropdown();
        resultsArea.style.display = 'none';
        noData.style.display      = 'none';
    }

    function fetchNames() {
        const yr = veYear.value;
        resetNameWidget('Loading…');
        if (!yr) { resetNameWidget(); return; }

        fetch(`view_excel.php?ajax=get_names&year=${encodeURIComponent(yr)}`)
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(data => {
                if (data.error) throw new Error(data.error);
                if (data.length === 0) { resetNameWidget('No records found for this year'); return; }

                allNames                        = data.map(row => row.name);
                veNameSearch.disabled           = false;
                veNameSearch.placeholder        = 'Type to search name…';
                veNameGroup.style.opacity       = '1';
                veNameGroup.style.pointerEvents = '';
            })
            .catch(err => { resetNameWidget('Error loading names'); console.error(err.message); });
    }

    function loadDetails(name) {
        const yr = veYear.value;
        if (!name || !yr) return;

        fetch(`view_excel.php?ajax=get_details&name=${encodeURIComponent(name)}&year=${encodeURIComponent(yr)}`)
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    resultsArea.style.display = 'none';
                    noData.style.display      = 'block';
                    return;
                }

                noData.style.display      = 'none';
                resultsArea.style.display = 'flex';

                let htmlSummary = '';
                let htmlDeduct  = '';

                data.forEach(row => {
                    htmlSummary += `
                        <tr>
                            <td>${escHtml(row.period_covered)}</td>
                            <td>${row.num_days}</td>
                            <td>${fmt(row.rate)}</td>
                            <td>${fmt(row.total_wage)}</td>
                            <td class="td-amt">${fmt(row.total_amount_due)}</td>
                        </tr>`;
                    htmlDeduct += `
                        <tr>
                            <td style="font-weight:600;">${escHtml(row.period_covered)}</td>
                            <td>${fmt(row.lbp)}</td>
                            <td>${fmt(row.pagibig_cont)}</td>
                            <td>${fmt(row.pagibig_mpl)}</td>
                            <td>${fmt(row.sss_cont)}</td>
                            <td>${fmt(row.late_deduction)}</td>
                            <td>${fmt(row.nursery_prod)}</td>
                        </tr>`;
                });

                document.getElementById('bodySummary').innerHTML    = htmlSummary;
                document.getElementById('bodyDeductions').innerHTML = htmlDeduct;
            });
    }

    veYear.addEventListener('change', fetchNames);

})();