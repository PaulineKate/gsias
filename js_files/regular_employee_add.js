(function () {

    /* ══════════════════════════════════════════════════
       HELPERS
    ══════════════════════════════════════════════════ */

    /** Set or clear a field-level error; returns true if no error */
    function fieldErr(inp, msg) {
        const errEl = inp.closest('.emp-group')?.querySelector('.emp-field-error');
        inp.classList.toggle('emp-input--error', !!msg);
        if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
        return !msg;
    }

    /** Show the top alert banner and auto-fade after 5 s */
    function showAlert(msg, type) {
        const el = document.getElementById('empAlert');
        el.className   = 'emp-alert ' + type;
        el.innerHTML   = msg;
        el.style.opacity    = '1';
        el.style.transition = '';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        clearTimeout(el._t);
        el._t = setTimeout(() => {
            el.style.transition = 'opacity 1s';
            el.style.opacity    = '0';
            setTimeout(() => {
                el.className = 'emp-alert';
                el.style.opacity = '';
                el.style.transition = '';
            }, 1000);
        }, 5000);
    }

    /** Show / hide a clear button based on whether the input has a value */
    function syncClear(input) {
        const btn = input.closest('.emp-input-wrap')?.querySelector('.emp-clear-btn');
        if (btn) btn.style.display = input.value ? 'flex' : 'none';
    }

    /* Auto-dismiss PHP-rendered alert on page load */
    const phpAlert = document.querySelector('.emp-alert.success, .emp-alert.error, .emp-alert.warning');
    if (phpAlert && phpAlert.id !== 'empAlert') {
        setTimeout(() => {
            phpAlert.style.transition = 'opacity 1s';
            phpAlert.style.opacity    = '0';
            setTimeout(() => phpAlert.style.display = 'none', 1000);
        }, 4000);
    }

    /* ══════════════════════════════════════════════════
       CLEAR BUTTONS — wire up all at once
    ══════════════════════════════════════════════════ */
    document.querySelectorAll('.emp-clear-btn').forEach(function (btn) {
        const targetId = btn.dataset.target;
        const inp      = targetId ? document.getElementById(targetId) : null;
        if (!inp) return;

        /* Hide button initially if field is empty */
        btn.style.display = inp.value ? 'flex' : 'none';

        btn.addEventListener('click', function () {
            inp.value = '';
            inp.classList.remove('emp-input--error');
            fieldErr(inp, '');
            btn.style.display = 'none';

            /* Extra reset for emp_id status indicators */
            if (targetId === 'empId') {
                setIdStatus('', '');
            }
            /* Extra reset for designation */
            if (targetId === 'empDesignation') {
                closeDdGlobal();
            }

            inp.focus();
            inp.dispatchEvent(new Event('input', { bubbles: true }));
        });

        /* Keep clear button in sync as user types */
        inp.addEventListener('input', () => syncClear(inp));
    });

    /* ══════════════════════════════════════════════════
       NAME INPUTS — Surname, First Name, Middle Name
    ══════════════════════════════════════════════════ */
    function attachNameInput(input, required) {
        if (!input) return;

        input.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End',' ','-',"'"];
            if (nav.includes(e.key) || /^[a-zA-ZÑñ]$/.test(e.key)) return;
            e.preventDefault();
            fieldErr(input, 'Only letters, spaces, hyphens, and apostrophes are allowed.');
        });

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
            syncClear(this);

            const val = this.value.trim();
            if (required && val === '')       fieldErr(this, 'This field is required.');
            else if (val !== '' && val.length < 2) fieldErr(this, 'Must be at least 2 characters.');
            else                               fieldErr(this, '');
        });

        input.addEventListener('blur', function () {
            const val = this.value.trim();
            if (required && val === '') fieldErr(this, 'This field is required.');
        });
    }

    attachNameInput(document.getElementById('empLastName'),   true);
    attachNameInput(document.getElementById('empFirstName'),  true);
    attachNameInput(document.getElementById('empMiddleName'), false);

    /* ══════════════════════════════════════════════════
       EMPLOYEE ID — format + duplicate AJAX check
    ══════════════════════════════════════════════════ */
    (function () {
        const inp     = document.getElementById('empId');
        const spinner = document.getElementById('empIdSpinner');
        const errEl   = document.getElementById('errEmpId');
        if (!inp) return;

        let checkTimer  = null;
        let lastChecked = '';
        let idTaken     = false;

        function setStatus(type, msg) {
            /* type: '' | 'checking' | 'ok' | 'taken' | 'error' */
            const statusEl = document.getElementById('empIdStatus');
            if (!statusEl) return;
            statusEl.className = 'emp-id-status' + (type ? ' emp-id-status--' + type : '');
            statusEl.textContent = msg;
        }

        /* export for clear button */
        window._empIdSetStatus = setStatus;

        function setErr(msg) {
            if (!errEl) return;
            errEl.textContent = msg;
            errEl.style.display = msg ? 'block' : 'none';
            inp.classList.toggle('emp-input--error', !!msg);
        }

        function checkDuplicate(formatted) {
            if (formatted === lastChecked) return;
            lastChecked = formatted;
            idTaken     = false;

            setStatus('checking', 'Checking…');
            if (spinner) spinner.style.display = 'inline-block';

            fetch('regular_employee_add.php?ajax=check_emp_id&id=' + encodeURIComponent(formatted))
                .then(r => r.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    if (data.exists) {
                        idTaken = true;
                        setStatus('taken', '✗ ID already in use');
                        setErr('This Employee ID is already registered.');
                        inp.classList.add('emp-input--error');
                    } else {
                        idTaken = false;
                        setStatus('ok', '✓ Available');
                        setErr('');
                        inp.classList.remove('emp-input--error');
                    }
                })
                .catch(() => {
                    if (spinner) spinner.style.display = 'none';
                    setStatus('error', '⚠ Check failed');
                });
        }

        inp.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || e.key === '.' || e.key === '-') return;
            if (/^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            /* Auto-format digits into ####.#-### */
            const caret = this.selectionStart;
            let d = this.value.replace(/[^0-9]/g, '');
            let f = '';
            if (d.length > 0) f  = d.substring(0, 4);
            if (d.length > 4) f += '.' + d[4];
            if (d.length > 5) f += '-' + d.substring(5, 8);
            this.value = f;

            syncClear(this);
            setStatus('', '');
            lastChecked = '';
            idTaken     = false;

            const valid = /^\d{4}\.\d-\d{3}$/.test(f);
            if (!f)           setErr('Employee ID is required.');
            else if (!valid)  setErr('Format: ####.#-### (e.g. 1061.0-001)');
            else              setErr('');

            /* Debounce duplicate check — fires 600 ms after last keystroke */
            clearTimeout(checkTimer);
            if (valid) {
                checkTimer = setTimeout(() => checkDuplicate(f), 600);
            }
        });

        inp.addEventListener('blur', function () {
            clearTimeout(checkTimer);
            const f = this.value.trim();
            if (!f) { setErr('Employee ID is required.'); setStatus('', ''); return; }
            const valid = /^\d{4}\.\d-\d{3}$/.test(f);
            if (!valid) { setErr('Format: ####.#-### (e.g. 1061.0-001)'); return; }
            /* Run check immediately on blur if not yet done */
            if (f !== lastChecked) checkDuplicate(f);
        });

        /* Expose idTaken state for submit validation */
        inp._isIdTaken = function () { return idTaken; };
    })();

    /* Helper so clear button can also reset status indicator */
    function setIdStatus(type, msg) {
        if (window._empIdSetStatus) window._empIdSetStatus(type, msg);
        const spinner = document.getElementById('empIdSpinner');
        if (spinner) spinner.style.display = 'none';
        const errEl = document.getElementById('errEmpId');
        if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
        const inp = document.getElementById('empId');
        if (inp) inp.classList.remove('emp-input--error');
    }

    /* ══════════════════════════════════════════════════
       SALARY — digits + up to 2 decimal places
    ══════════════════════════════════════════════════ */
    (function () {
        const inp = document.getElementById('empSalary');
        if (!inp) return;

        inp.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key)) return;
            if (/^[0-9]$/.test(e.key)) return;
            if (e.key === '.' && !this.value.includes('.')) return;
            e.preventDefault();
            fieldErr(inp, 'Only numbers are allowed.');
        });

        inp.addEventListener('input', function () {
            if (/^\d+\.\d{3,}$/.test(this.value))
                this.value = parseFloat(this.value).toFixed(2);
            syncClear(this);
            fieldErr(inp, this.value.trim() === '' ? 'Salary is required.' : '');
        });

        inp.addEventListener('blur', function () {
            if (this.value.trim() === '') fieldErr(inp, 'Salary is required.');
        });
    })();

    /* ══════════════════════════════════════════════════
       DESIGNATION AUTOCOMPLETE
    ══════════════════════════════════════════════════ */
    let closeDdGlobal = function () {};  /* filled in below */

    (function () {
        const inp   = document.getElementById('empDesignation');
        const ul    = document.getElementById('empDesigDropdown');
        const errEl = document.getElementById('errDesignation');
        if (!inp || !ul) return;

        let options = [];
        let loaded  = false;

        function loadDesignations(cb) {
            if (loaded) { cb(options); return; }
            fetch('regular_employee_add.php?ajax=get_designations')
                .then(r => r.json())
                .then(data => {
                    options = Array.isArray(data) ? data.map(v => v.toUpperCase()) : [];
                    loaded  = true;
                    cb(options);
                })
                .catch(() => { loaded = true; cb([]); });
        }

        function showErr(msg) {
            if (!errEl) return;
            errEl.textContent = msg;
            errEl.style.display = msg ? 'block' : 'none';
            inp.classList.toggle('emp-input--error', !!msg);
        }

        function openDd(filtered) {
            ul.innerHTML = '';
            if (!filtered.length) {
                const li = document.createElement('li');
                li.className   = 'empty';
                li.textContent = 'No matches found';
                ul.appendChild(li);
            } else {
                filtered.forEach(val => {
                    const li = document.createElement('li');
                    li.textContent = val;
                    li.addEventListener('mousedown', e => {
                        e.preventDefault();
                        inp.value = val;
                        syncClear(inp);
                        showErr('');
                        closeDd();
                    });
                    ul.appendChild(li);
                });
            }
            ul.style.display = 'block';
        }

        function closeDd() { ul.style.display = 'none'; ul.innerHTML = ''; }
        closeDdGlobal = closeDd;   /* expose for clear button */

        inp.addEventListener('focus', function () {
            loadDesignations(opts => {
                const q = this.value.trim().toUpperCase();
                openDd(q ? opts.filter(o => o.includes(q)) : opts);
            });
        });

        inp.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
            syncClear(this);
            showErr('');
            loadDesignations(opts => {
                const q = this.value.trim();
                openDd(q ? opts.filter(o => o.includes(q)) : opts);
            });
        });

        inp.addEventListener('keydown', function (e) {
            const items = ul.querySelectorAll('li:not(.empty)');
            let idx = Array.from(items).findIndex(li => li.classList.contains('active'));
            if (e.key === 'ArrowDown')     { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp')  { e.preventDefault(); idx = Math.max(idx - 1, 0); }
            else if ((e.key === 'Enter' || e.key === 'Tab') && idx >= 0) {
                e.preventDefault();
                inp.value = items[idx].textContent;
                syncClear(inp);
                showErr('');
                closeDd();
                return;
            }
            else if (e.key === 'Escape') { closeDd(); return; }
            else return;
            items.forEach((li, i) => li.classList.toggle('active', i === idx));
            if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
        });

        inp.addEventListener('blur', function () {
            setTimeout(() => {
                closeDd();
                const typed = this.value.trim().toUpperCase();
                if (!typed) { showErr('Designation is required.'); return; }
                loadDesignations(opts => {
                    if (opts.some(o => o === typed)) {
                        inp.value = typed;
                        syncClear(inp);
                        showErr('');
                    } else {
                        inp.value = '';
                        syncClear(inp);
                        showErr('Please select a valid designation from the list.');
                    }
                });
            }, 160);
        });

        document.addEventListener('click', e => {
            if (!inp.closest('.emp-ac-wrap')?.contains(e.target)) closeDd();
        });
    })();

    /* ══════════════════════════════════════════════════
       SAVE BUTTON — full validation + confirm modal
    ══════════════════════════════════════════════════ */
    (function () {
        const saveBtn    = document.getElementById('empSaveBtn');
        const overlay    = document.getElementById('empOverlay');
        const modalBody  = document.getElementById('empModalBody');
        const btnCancel  = document.getElementById('empModalCancel');
        const btnConfirm = document.getElementById('empModalConfirm');
        const form       = document.getElementById('empForm');

        function validateAll() {
            const errors = [];

            const ln = document.getElementById('empLastName').value.trim();
            if (!ln)           errors.push('Last name is required.');
            else if (ln.length < 2) errors.push('Last name must be at least 2 characters.');

            const fn = document.getElementById('empFirstName').value.trim();
            if (!fn)           errors.push('First name is required.');
            else if (fn.length < 2) errors.push('First name must be at least 2 characters.');

            const eid = document.getElementById('empId');
            const eidVal = eid ? eid.value.trim() : '';
            if (!eidVal)                               errors.push('Employee ID is required.');
            else if (!/^\d{4}\.\d-\d{3}$/.test(eidVal)) errors.push('Employee ID format: ####.#-###');
            else if (eid && typeof eid._isIdTaken === 'function' && eid._isIdTaken())
                errors.push('This Employee ID is already in use. Please use a different ID.');

            const desig = document.getElementById('empDesignation').value.trim();
            if (!desig) errors.push('Designation is required.');

            const sal = document.getElementById('empSalary').value.trim();
            if (!sal)                       errors.push('Salary is required.');
            else if (isNaN(parseFloat(sal))) errors.push('Salary must be a valid number.');

            return errors;
        }

        saveBtn.addEventListener('click', function () {
            const errors = validateAll();
            if (errors.length > 0) {
                showAlert(errors.join('<br>'), 'error');
                return;
            }

            const standing = document.getElementById('empStanding').value;
            const name = document.getElementById('empFirstName').value.trim()
                       + ' ' + document.getElementById('empLastName').value.trim();
            modalBody.innerHTML =
                'You are about to save a new employee record for<br>' +
                '<strong>' + name + '</strong>.<br><br>' +
                'Department: <strong>PGSO</strong> &nbsp;|&nbsp; ' +
                'Standing: <strong>' + standing.charAt(0).toUpperCase() + standing.slice(1) + '</strong>' +
                '<br><br>Continue?';
            overlay.classList.add('active');
        });

        btnCancel.addEventListener('click',  () => overlay.classList.remove('active'));
        btnConfirm.addEventListener('click', () => { overlay.classList.remove('active'); form.submit(); });
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('active'); });
    })();

})();