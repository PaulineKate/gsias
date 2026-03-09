(function () {
    const yearSel      = document.getElementById('piYear');
    const monthSel     = document.getElementById('piMonth');
    const nameSearch   = document.getElementById('piNameSearch');
    const nameDropdown = document.getElementById('piNameDropdown');
    const nameHidden   = document.getElementById('piNameHidden');
    const nameGroup    = document.getElementById('piNameGroup');
    const desigGroup   = document.getElementById('piDesigGroup');
    const desigInput   = document.getElementById('piDesignation');
    const rateInput    = document.getElementById('piRate');
    const numDays      = document.getElementById('piNumDays');
    const periodInput  = document.getElementById('piPeriod');
    const periodHint   = document.getElementById('piPeriodHint');
    const alertEl      = document.getElementById('piAlert');
    const saveBtn      = document.getElementById('piBtnSave');
    const piForm       = document.getElementById('piForm');

    // Modal elements
    const overlay      = document.getElementById('piOverlay');
    const modalBody    = document.getElementById('piModalBody');
    const modalCancel  = document.getElementById('piModalCancel');
    const modalConfirm = document.getElementById('piModalConfirm');

    let currentRate       = 0;
    let existingPeriods   = [];
    let duplicateDetected = false;
    let allContracts      = [];   // full list loaded for the selected year+month
    let alertTimer        = null;

    /* ── Month number → canonical abbreviation ── */
    const monthNumToAbbr = {
        1:'Jan.', 2:'Feb.', 3:'Mar.', 4:'Apr.', 5:'May',
        6:'Jun.', 7:'Jul.', 8:'Aug.', 9:'Sep.', 10:'Oct.',
        11:'Nov.', 12:'Dec.'
    };

    /* ── Month abbreviation/name → number ── */
    const monthMap = {
        jan:1, feb:2, mar:3, apr:4, may:5, jun:6,
        jul:7, aug:8, sep:9, oct:10, nov:11, dec:12
    };
    function periodToMonth(str) {
        if (!str) return null;
        const lower = str.toLowerCase();
        for (const [abbr, num] of Object.entries(monthMap)) {
            if (lower.includes(abbr)) return num;
        }
        return null;
    }

    /* ── Get the required prefix for the currently selected month ── */
    function getRequiredPrefix() {
        const mo = parseInt(monthSel.value, 10);
        return mo ? monthNumToAbbr[mo] : null;
    }

    /* ── Auto-fill / update the period prefix when month changes ── */
    function syncPeriodPrefix() {
        const prefix = getRequiredPrefix();
        if (!prefix) {
            periodInput.value = '';
            return;
        }
        const stripped = periodInput.value.replace(/^[A-Za-z]+\.?\s*/, '').trimStart();
        periodInput.value = prefix + (stripped ? ' ' + stripped : ' ');
        const len = periodInput.value.length;
        periodInput.setSelectionRange(len, len);
        checkDuplicate();
    }

    /* ── Enforce correct prefix while the user is typing ── */
    function enforcePeriodPrefix() {
        const prefix = getRequiredPrefix();
        if (!prefix) return;
        setTimeout(() => {
            const val = periodInput.value;
            if (!val.startsWith(prefix)) {
                const stripped = val.replace(/^[A-Za-z]+\.?\s*/, '').trimStart();
                periodInput.value = prefix + (stripped ? ' ' + stripped : ' ');
                const len = periodInput.value.length;
                periodInput.setSelectionRange(len, len);
            }
            checkDuplicate();
        }, 0);
    }

    /* ── Format number ── */
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    /* ── Recalculate summary ── */
    function recalc() {
        const days = parseFloat(numDays.value) || 0;
        const wage = days * currentRate;
        let dedTotal = 0;
        document.querySelectorAll('.deduction-input').forEach(inp => {
            dedTotal += parseFloat(inp.value) || 0;
        });
        document.getElementById('sumTotalWage').textContent = fmt(wage);
        document.getElementById('sumTotalDed').textContent  = fmt(dedTotal);
        document.getElementById('sumAmtDue').textContent    = fmt(wage - dedTotal);
    }

    /* ── Show inline alert — auto-dismisses after 4 s, fades at 3 s ── */
    function showAlert(msg, type) {
        if (alertTimer) clearTimeout(alertTimer);
        alertEl.style.transition = '';
        alertEl.style.opacity    = '1';
        alertEl.className  = 'pi-alert ' + type;
        alertEl.innerHTML  = msg;
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Start fade after 3 s, remove after 4 s
        alertTimer = setTimeout(() => {
            alertEl.style.transition = 'opacity 1s ease';
            alertEl.style.opacity    = '0';
            setTimeout(() => clearAlert(), 1000);
        }, 3000);
    }
    function clearAlert() {
        if (alertTimer) { clearTimeout(alertTimer); alertTimer = null; }
        alertEl.className    = 'pi-alert';
        alertEl.textContent  = '';
        alertEl.style.opacity    = '1';
        alertEl.style.transition = '';
    }

    /* ── Also auto-dismiss the PHP-rendered alert at top of page ── */
    const phpAlert = document.querySelector('.pi-alert.success, .pi-alert.error, .pi-alert.warning');
    if (phpAlert && phpAlert.id !== 'piAlert') {
        phpAlert.style.transition = '';
        phpAlert.style.opacity    = '1';
        setTimeout(() => {
            phpAlert.style.transition = 'opacity 1s ease';
            phpAlert.style.opacity    = '0';
            setTimeout(() => { phpAlert.style.display = 'none'; }, 1000);
        }, 3000);
    }

    /* ── Check duplicate period ── */
    function checkDuplicate() {
        const typedMonth = periodToMonth(periodInput.value);
        if (!typedMonth || existingPeriods.length === 0) {
            periodHint.style.display = 'none';
            duplicateDetected = false;
            return;
        }
        const clash = existingPeriods.some(p => periodToMonth(p) === typedMonth);
        duplicateDetected = clash;
        periodHint.style.display = clash ? 'block' : 'none';
    }

    /* ════════════════════════════════════════════════
       SEARCHABLE NAME DROPDOWN
    ════════════════════════════════════════════════ */

    /* ── Select a contract row ── */
    function selectContract(row) {
        nameHidden.value  = row.jo_id;
        nameSearch.value  = row.name;
        desigInput.value  = row.designation || '';
        currentRate       = parseFloat(row.rate) || 0;
        rateInput.value   = currentRate ? fmt(currentRate) : '';
        desigGroup.style.opacity = currentRate ? '1' : '0.4';
        recalc();
        fetchExistingPeriods(row.jo_id);
        closeDropdown();
    }

    /* ── Clear current contract selection ── */
    function clearSelection() {
        nameHidden.value  = '';
        desigInput.value  = '';
        currentRate       = 0;
        rateInput.value   = '';
        desigGroup.style.opacity = '0.4';
        existingPeriods   = [];
        duplicateDetected = false;
        periodHint.style.display = 'none';
        recalc();
    }

    /* ── Render filtered dropdown list ── */
    function renderDropdown(filter) {
        const q = (filter || '').toLowerCase().trim();
        const matches = q
            ? allContracts.filter(r => r.name.toLowerCase().includes(q))
            : allContracts;

        nameDropdown.innerHTML = '';

        if (matches.length === 0) {
            const li = document.createElement('li');
            li.className = 'pi-dd-empty';
            li.textContent = 'No matches found';
            nameDropdown.appendChild(li);
        } else {
            matches.forEach(row => {
                const li = document.createElement('li');
                li.className = 'pi-dd-item';
                li.dataset.joId = row.jo_id;

                // Highlight the matching part
                const displayName = `${row.name} (${row.date_from} to ${row.date_to})`;
                if (q) {
                    const idx = displayName.toLowerCase().indexOf(q);
                    if (idx !== -1) {
                        li.innerHTML =
                            escHtml(displayName.slice(0, idx)) +
                            `<mark>${escHtml(displayName.slice(idx, idx + q.length))}</mark>` +
                            escHtml(displayName.slice(idx + q.length));
                    } else {
                        li.textContent = displayName;
                    }
                } else {
                    li.textContent = displayName;
                }

                li.addEventListener('mousedown', (e) => {
                    e.preventDefault(); // prevent blur before click fires
                    selectContract(row);
                });
                nameDropdown.appendChild(li);
            });
        }

        openDropdown();
    }

    function openDropdown() {
        nameDropdown.style.display = 'block';
    }
    function closeDropdown() {
        nameDropdown.style.display = 'none';
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ── Keyboard navigation inside the dropdown ── */
    nameSearch.addEventListener('keydown', function (e) {
        const items = nameDropdown.querySelectorAll('.pi-dd-item');
        const active = nameDropdown.querySelector('.pi-dd-item.active');
        let idx = Array.from(items).indexOf(active);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (nameDropdown.style.display === 'none') renderDropdown(nameSearch.value);
            idx = Math.min(idx + 1, items.length - 1);
            items.forEach(i => i.classList.remove('active'));
            if (items[idx]) items[idx].classList.add('active');
            items[idx] && items[idx].scrollIntoView({ block: 'nearest' });

        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = Math.max(idx - 1, 0);
            items.forEach(i => i.classList.remove('active'));
            if (items[idx]) items[idx].classList.add('active');
            items[idx] && items[idx].scrollIntoView({ block: 'nearest' });

        } else if (e.key === 'Tab' || e.key === 'Enter') {
            const highlighted = nameDropdown.querySelector('.pi-dd-item.active')
                             || nameDropdown.querySelector('.pi-dd-item');
            if (highlighted && nameDropdown.style.display !== 'none') {
                e.preventDefault();
                const joId = highlighted.dataset.joId;
                const row  = allContracts.find(r => String(r.jo_id) === String(joId));
                if (row) selectContract(row);
                // Move focus to next logical field
                periodInput.focus();
            }

        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    nameSearch.addEventListener('input', function () {
        clearSelection();
        if (!allContracts.length) return;
        renderDropdown(this.value);
    });

    nameSearch.addEventListener('focus', function () {
        if (allContracts.length) renderDropdown(this.value);
    });

    nameSearch.addEventListener('blur', function () {
        // Small delay so mousedown on item fires first
        setTimeout(closeDropdown, 150);
    });

    /* ════════════════════════════════════════════════
       DATA FETCHING
    ════════════════════════════════════════════════ */

    /* ── Reset the name search widget ── */
    function resetNameWidget(placeholderMsg) {
        allContracts      = [];
        nameHidden.value  = '';
        nameSearch.value  = '';
        nameSearch.placeholder = placeholderMsg || '— Select Year & Month first —';
        nameSearch.disabled    = true;
        nameGroup.style.opacity       = '0.4';
        nameGroup.style.pointerEvents = 'none';
        clearSelection();
        closeDropdown();
    }

    /* ── Fetch names for selected year + month ── */
    function fetchNames(preselectJoId = null) {
        const yr = yearSel.value;
        const mo = monthSel.value;
        clearAlert();

        resetNameWidget('Loading\u2026');
        existingPeriods   = [];
        duplicateDetected = false;
        periodHint.style.display = 'none';
        recalc();

        if (!yr || !mo) {
            resetNameWidget();
            return;
        }

        fetch(`payment_index_add.php?ajax=get_names&year=${encodeURIComponent(yr)}&month=${encodeURIComponent(mo)}`)
            .then(r => {
                if (!r.ok) throw new Error(`Server responded with HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);

                if (data.length === 0) {
                    resetNameWidget('No contracts found for this period');
                    return;
                }

                allContracts = data;
                nameSearch.disabled    = false;
                nameSearch.placeholder = 'Type to search name\u2026';
                nameGroup.style.opacity       = '1';
                nameGroup.style.pointerEvents = '';

                // Re-select the previously chosen contract if available
                if (preselectJoId) {
                    const targetId = String(preselectJoId);
                    const row = allContracts.find(r => String(r.jo_id) === targetId);
                    if (row) selectContract(row);
                }
            })
            .catch(err => {
                resetNameWidget('Error loading names');
                showAlert('Could not load contracts: ' + err.message, 'error');
            });
    }

    /* ── Fetch existing periods for the selected jo_id ── */
    function fetchExistingPeriods(joId) {
        existingPeriods   = [];
        duplicateDetected = false;
        periodHint.style.display = 'none';

        if (!joId) return;

        fetch(`payment_index_add.php?ajax=get_periods&jo_id=${encodeURIComponent(joId)}`)
            .then(r => r.json())
            .then(data => {
                if (Array.isArray(data)) {
                    existingPeriods = data;
                    checkDuplicate();
                }
            })
            .catch(() => { /* non-critical */ });
    }

    /* ════════════════════════════════════════════════
       EVENT LISTENERS
    ════════════════════════════════════════════════ */

    yearSel.addEventListener('change', () => fetchNames());

    monthSel.addEventListener('change', () => {
        fetchNames();
        syncPeriodPrefix();
    });

    numDays.addEventListener('input', recalc);

    periodInput.addEventListener('input', enforcePeriodPrefix);

    periodInput.addEventListener('keydown', function (e) {
        const prefix = getRequiredPrefix();
        if (!prefix) return;
        if (e.key === 'Backspace' || e.key === 'Delete') {
            const selStart = this.selectionStart;
            const selEnd   = this.selectionEnd;
            if (selStart <= prefix.length || selEnd < prefix.length) {
                e.preventDefault();
            }
        }
    });

    document.querySelectorAll('.deduction-input').forEach(inp => {
        inp.addEventListener('input', recalc);
    });

    /* ── Save button ── */
    saveBtn.addEventListener('click', function () {
        clearAlert();

        const errors = [];
        if (!nameHidden.value)          errors.push('Please select a contract / name.');
        if (!periodInput.value.trim())  errors.push('Period covered is required.');
        if (!(parseFloat(numDays.value) > 0)) errors.push('Number of days must be greater than 0.');
        if (!yearSel.value)             errors.push('Please select a year.');

        const prefix = getRequiredPrefix();
        if (prefix && !periodInput.value.startsWith(prefix)) {
            errors.push(`Period covered must start with "${prefix}" for the selected month.`);
        }

        document.querySelectorAll('.deduction-input').forEach(inp => {
            if (parseFloat(inp.value) < 0) {
                errors.push(inp.closest('.pi-group').querySelector('.pi-label').textContent + ' cannot be negative.');
            }
        });

        if (errors.length > 0) {
            showAlert(errors.join('<br>'), 'error');
            return;
        }

        if (duplicateDetected) {
            const selectedName = nameSearch.value;
            modalBody.innerHTML =
                `An entry for <strong>${escHtml(selectedName)}</strong> in the same month already exists.<br><br>` +
                `Saving will permanently <strong>delete the previous entry</strong> and replace it with this one. Continue?`;
            overlay.classList.add('active');
        } else {
            piForm.submit();
        }
    });

    modalCancel.addEventListener('click',  () => overlay.classList.remove('active'));
    modalConfirm.addEventListener('click', () => {
        overlay.classList.remove('active');
        piForm.submit();
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });

    /* ── On page load after POST: restore year+month+name ── */
    if (typeof PHP_SAVED !== 'undefined' && PHP_SAVED.year && PHP_SAVED.month) {
        fetchNames(PHP_SAVED.jo_id || null);
        checkDuplicate();
    }

})();