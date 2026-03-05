(function () {
    const yearSel    = document.getElementById('piYear');
    const monthSel   = document.getElementById('piMonth');
    const nameSel    = document.getElementById('piName');
    const nameGroup  = document.getElementById('piNameGroup');
    const desigGroup = document.getElementById('piDesigGroup');
    const desigInput = document.getElementById('piDesignation');
    const rateInput  = document.getElementById('piRate');
    const numDays    = document.getElementById('piNumDays');
    const periodInput= document.getElementById('piPeriod');
    const periodHint = document.getElementById('piPeriodHint');
    const alertEl    = document.getElementById('piAlert');
    const saveBtn    = document.getElementById('piBtnSave');
    const piForm     = document.getElementById('piForm');

    // Modal elements
    const overlay      = document.getElementById('piOverlay');
    const modalBody    = document.getElementById('piModalBody');
    const modalCancel  = document.getElementById('piModalCancel');
    const modalConfirm = document.getElementById('piModalConfirm');

    let currentRate       = 0;
    let existingPeriods   = [];   // all period_covered strings for the current jo_id
    let duplicateDetected = false;

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

    /* ── Show inline alert ── */
    function showAlert(msg, type) {
        alertEl.className = 'pi-alert ' + type;
        alertEl.innerHTML = msg;
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function clearAlert() {
        alertEl.className = 'pi-alert';
        alertEl.textContent = '';
    }

    /* ── Map month abbreviation → number ── */
    const monthMap = {
        jan:1, feb:2, mar:3, apr:4, may:5, jun:6,
        jul:7, aug:8, sep:9, oct:10, nov:11, dec:12
    };
    function periodToMonth(str) {
        const lower = str.toLowerCase();
        for (const [abbr, num] of Object.entries(monthMap)) {
            if (lower.includes(abbr)) return num;
        }
        return null;
    }

    /* ── Check if typed period clashes with an existing entry ── */
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

    /* ── Fetch names for selected year + month ── */
    function fetchNames() {
        const yr = yearSel.value;
        const mo = monthSel.value;
        clearAlert();

        // Reset name selector
        nameSel.innerHTML = '<option value="">Loading…</option>';
        nameSel.disabled  = true;
        nameGroup.style.opacity       = '0.4';
        nameGroup.style.pointerEvents = 'none';
        desigGroup.style.opacity      = '0.4';
        desigInput.value  = '';
        rateInput.value   = '';
        currentRate       = 0;
        existingPeriods   = [];
        duplicateDetected = false;
        periodHint.style.display = 'none';
        recalc();

        if (!yr || !mo) {
            nameSel.innerHTML = '<option value="">— Select Year &amp; Month —</option>';
            return;
        }

        fetch(`payment_index_add.php?ajax=get_names&year=${encodeURIComponent(yr)}&month=${encodeURIComponent(mo)}`)
            .then(r => {
                if (!r.ok) throw new Error(`Server responded with HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);

                nameSel.innerHTML = '<option value="">— Select Name —</option>';
                if (data.length === 0) {
                    nameSel.innerHTML = '<option value="">No contracts found for this period</option>';
                } else {
                    data.forEach(row => {
                        const opt = document.createElement('option');
                        opt.value = row.jo_id;
                        opt.textContent = `${row.name} (${row.date_from} to ${row.date_to})`;
                        opt.dataset.designation = row.designation;
                        opt.dataset.rate        = row.rate;
                        nameSel.appendChild(opt);
                    });
                    nameSel.disabled = false;
                    nameGroup.style.opacity       = '1';
                    nameGroup.style.pointerEvents = '';
                }
            })
            .catch(err => {
                nameSel.innerHTML = '<option value="">Error loading names</option>';
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
                    checkDuplicate(); // re-check in case period is already typed
                }
            })
            .catch(() => { /* non-critical – duplicate hint just won't show */ });
    }

    /* ── Event listeners ── */
    yearSel.addEventListener('change',  fetchNames);
    monthSel.addEventListener('change', fetchNames);

    nameSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        desigInput.value = opt.dataset.designation || '';
        currentRate      = parseFloat(opt.dataset.rate) || 0;
        rateInput.value  = currentRate ? fmt(currentRate) : '';
        desigGroup.style.opacity = currentRate ? '1' : '0.4';
        recalc();
        fetchExistingPeriods(this.value);
    });

    numDays.addEventListener('input',   recalc);
    periodInput.addEventListener('input', checkDuplicate);
    document.querySelectorAll('.deduction-input').forEach(inp => {
        inp.addEventListener('input', recalc);
    });

    /* ── Save button click: validate → maybe confirm → submit ── */
    saveBtn.addEventListener('click', function () {
        clearAlert();

        // Client-side validation
        const errors = [];
        if (!nameSel.value)          errors.push('Please select a contract / name.');
        if (!periodInput.value.trim()) errors.push('Period covered is required.');
        if (!(parseFloat(numDays.value) > 0)) errors.push('Number of days must be greater than 0.');
        if (!yearSel.value)          errors.push('Please select a year.');

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
            // Show confirmation modal
            const selectedName = nameSel.options[nameSel.selectedIndex].textContent;
            modalBody.innerHTML =
                `An entry for <strong>${selectedName}</strong> in the same month already exists.<br><br>` +
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

    // Close modal on backdrop click
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });
})();