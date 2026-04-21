(function () {

    /* Sets or clears a field-level error message */
    function fieldErr(el, msg) {
        el.classList.toggle('emp-input--error', !!msg);
        const errEl = el.closest('.emp-group')?.querySelector('.emp-field-error');
        if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
        return !msg;
    }

    /* Shows the top alert bar and auto-dismisses after 4s */
    function showAlert(msg, type) {
        const el = document.getElementById('empAlert');
        el.className = 'emp-alert ' + type;
        el.innerHTML = msg;
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        clearTimeout(el._t); 
        el._t = setTimeout(() => {
            el.style.transition = 'opacity 1s';
            el.style.opacity    = '0';
            setTimeout(() => { el.className = 'emp-alert'; el.style.opacity = ''; el.style.transition = ''; }, 1000);
        }, 4000);
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

    /* Attaches uppercase + letters-only validation to a name input */
    function attachNameInput(input, errId, required) {
        input.addEventListener('keydown', function (e) {
            const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End',' ','-',"'"];
            if (allowed.includes(e.key)) return;
            if (/^[a-zA-ZÑñ]$/.test(e.key)) return;
            e.preventDefault();
            fieldErr(input, 'Only letters are allowed in names.');
        });

        input.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);

            const errEl = document.getElementById(errId);
            const val   = this.value.trim();
            let msg = '';
            if (required && val === '') msg = 'This field is required.';
            else if (val !== '' && val.length < 2) msg = 'Must be at least 2 characters.';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    }

    attachNameInput(document.getElementById('empLastName'),   'errLastName',   true);
    attachNameInput(document.getElementById('empFirstName'),  'errFirstName',  true);
    attachNameInput(document.getElementById('empMiddleName'), 'errMiddleName', false);

    /* Auto-formats emp_id as ####.#-### and blocks non-digits */
    (function () {
        const inp = document.getElementById('empId');
        if (!inp) return;

        inp.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || e.key === '.' || e.key === '-') return;
            if (/^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            let d = this.value.replace(/[^0-9]/g, '');
            let f = '';
            if (d.length > 0) f += d.substring(0, 4);
            if (d.length > 4) f += '.' + d[4];
            if (d.length > 5) f += '-' + d.substring(5, 8);
            this.value = f;

            const valid = /^\d{4}\.\d-\d{3}$/.test(f);
            const errEl = document.getElementById('errEmpId');
            const msg   = f && !valid ? 'Format: ####.#-### (e.g. 1061.0-001)' : '';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    })();

    /* Blocks non-numeric keys in salary and limits to 2 decimal places */
    (function () {
        const inp = document.getElementById('empSalary');
        if (!inp) return;

        inp.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key)) return;
            if (/^[0-9]$/.test(e.key)) return;
            if (e.key === '.' && !this.value.includes('.')) return;
            e.preventDefault();
            const errEl = document.getElementById('errSalary');
            if (errEl && e.key.length === 1) { errEl.textContent = 'Only numbers are allowed.'; errEl.style.display = 'block'; }
            this.classList.add('emp-input--error');
        });

        inp.addEventListener('input', function () {
            if (/^\d+\.\d{3,}$/.test(this.value)) this.value = parseFloat(this.value).toFixed(2);
            const errEl = document.getElementById('errSalary');
            const msg   = this.value.trim() === '' ? 'Salary is required.' : '';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    })();

    /* Digits-only for SSS, or N/A */
    (function () {
        const inp = document.getElementById('empSss');
        if (!inp) return;
        function isNA(v) { return v.trim().toUpperCase() === 'N/A'; }

        inp.addEventListener('keydown', function (e) {
            if (isNA(this.value)) return;
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || /^[nNaA\/]$/.test(e.key) || /^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            if (isNA(this.value)) { this.value = 'N/A'; this.classList.remove('emp-input--error'); return; }
            if (/^[nN]/.test(this.value) && this.value.length <= 3) return;
            this.value = this.value.replace(/\D/g, '');
        });
    })();

    /* Auto-formats PhilHealth as ##-#########-#, or N/A */
    (function () {
        const inp = document.getElementById('empPhilhealth');
        if (!inp) return;
        function isNA(v) { return v.trim().toUpperCase() === 'N/A'; }

        inp.addEventListener('keydown', function (e) {
            if (isNA(this.value)) return;
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || /^[nNaA\/]$/.test(e.key) || /^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            if (isNA(this.value)) {
                this.value = 'N/A';
                this.classList.remove('emp-input--error');
                const errEl = document.getElementById('errPhilhealth');
                if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                return;
            }
            if (/^[nN]/.test(this.value) && this.value.length <= 3) return;
            let d = this.value.replace(/[^0-9]/g, '');
            let f = '';
            if (d.length > 0) f += d.substring(0, 2);
            if (d.length > 2) f += '-' + d.substring(2, 11);
            if (d.length > 11) f += '-' + d[11];
            this.value = f;

            const full  = /^\d{2}-\d{9}-\d$/.test(f);
            const errEl = document.getElementById('errPhilhealth');
            const msg   = f && !full ? 'Format: ##-#########-# or type N/A' : '';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    })();

    /* Auto-formats TIN as ###-###-###-####, or N/A */
    (function () {
        const inp = document.getElementById('empTin');
        if (!inp) return;
        function isNA(v) { return v.trim().toUpperCase() === 'N/A'; }

        inp.addEventListener('keydown', function (e) {
            if (isNA(this.value)) return;
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || /^[nNaA\/]$/.test(e.key) || /^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            if (isNA(this.value)) {
                this.value = 'N/A';
                this.classList.remove('emp-input--error');
                const errEl = document.getElementById('errTin');
                if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                return;
            }
            if (/^[nN]/.test(this.value) && this.value.length <= 3) return;
            let d = this.value.replace(/[^0-9]/g, '');
            let f = '';
            if (d.length > 0) f += d.substring(0, 3);
            if (d.length > 3) f += '-' + d.substring(3, 6);
            if (d.length > 6) f += '-' + d.substring(6, 9);
            if (d.length > 9) f += '-' + d.substring(9, 13);
            this.value = f;

            const full  = /^\d{3}-\d{3}-\d{3}-\d{4}$/.test(f);
            const errEl = document.getElementById('errTin');
            const msg   = f && !full ? 'Format: ###-###-###-#### or type N/A' : '';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    })();

    /* Enforces 12-digit rule for GSIS and Pag-IBIG, or N/A */
    ['empGsis', 'empPagibig'].forEach(function (id) {
        const inp   = document.getElementById(id);
        const errId = id === 'empGsis' ? 'errGsis' : 'errPagibig';
        if (!inp) return;
        function isNA(v) { return v.trim().toUpperCase() === 'N/A'; }

        inp.addEventListener('keydown', function (e) {
            if (isNA(this.value)) return;
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key) || /^[nNaA\/]$/.test(e.key) || /^[0-9]$/.test(e.key)) return;
            e.preventDefault();
        });

        inp.addEventListener('input', function () {
            if (isNA(this.value)) {
                this.value = 'N/A';
                this.classList.remove('emp-input--error');
                const errEl = document.getElementById(errId);
                if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                return;
            }
            if (/^[nN]/.test(this.value) && this.value.length <= 3) return;
            this.value = this.value.replace(/\D/g, '').substring(0, 12);
            const errEl = document.getElementById(errId);
            const msg   = this.value && this.value.length !== 12 ? 'Must be exactly 12 digits, or type N/A.' : '';
            if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
            this.classList.toggle('emp-input--error', !!msg);
        });
    });

    /* Designation autocomplete — fetches options via AJAX and filters live */
    (function () {
        const inp   = document.getElementById('empDesignation');
        const ul    = document.getElementById('empDesigDropdown');
        const errEl = document.getElementById('errDesignation');
        let options = [];
        let loaded  = false;

        function loadDesignations(cb) {
            if (loaded) { cb(options); return; }
            fetch('regular_employee_add.php?ajax=get_designations')
                .then(r => r.json())
                .then(data => { options = Array.isArray(data) ? data.map(v => v.toUpperCase()) : []; loaded = true; cb(options); })
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
                li.className = 'empty';
                li.textContent = 'No matches found';
                ul.appendChild(li);
            } else {
                filtered.forEach(val => {
                    const li = document.createElement('li');
                    li.textContent = val;
                    li.addEventListener('mousedown', e => { e.preventDefault(); inp.value = val; showErr(''); closeDd(); });
                    ul.appendChild(li);
                });
            }
            ul.style.display = 'block';
        }

        function closeDd() { ul.style.display = 'none'; ul.innerHTML = ''; }

        inp.addEventListener('focus', function () {
            loadDesignations(opts => { const q = this.value.trim().toUpperCase(); openDd(q ? opts.filter(o => o.includes(q)) : opts); });
        });

        inp.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
            showErr('');
            loadDesignations(opts => { const q = this.value.trim(); openDd(q ? opts.filter(o => o.includes(q)) : opts); });
        });

        inp.addEventListener('keydown', function (e) {
            const items = ul.querySelectorAll('li:not(.empty)');
            let idx = Array.from(items).findIndex(li => li.classList.contains('active'));
            if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
            else if ((e.key === 'Enter' || e.key === 'Tab') && idx >= 0) { e.preventDefault(); inp.value = items[idx].textContent; showErr(''); closeDd(); return; }
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
                    if (opts.some(o => o === typed)) { inp.value = typed; showErr(''); }
                    else { inp.value = ''; showErr('Please select a valid designation from the list.'); }
                });
            }, 160);
        });

        document.addEventListener('click', e => { if (!inp.closest('.emp-ac-wrap')?.contains(e.target)) closeDd(); });
    })();

    /* Validates all fields before submit; shows confirm modal on success */
    (function () {
        const saveBtn   = document.getElementById('empSaveBtn');
        const overlay   = document.getElementById('empOverlay');
        const modalBody = document.getElementById('empModalBody');
        const btnCancel = document.getElementById('empModalCancel');
        const btnConfirm= document.getElementById('empModalConfirm');
        const form      = document.getElementById('empForm');

        function naOk(v) { return v.toUpperCase() === 'N/A'; }

        function validateAll() {
            const errors = [];
            const ln = document.getElementById('empLastName').value.trim();
            if (!ln) errors.push('Last name is required.');
            else if (ln.length < 2) errors.push('Last name must be at least 2 characters.');

            const fn = document.getElementById('empFirstName').value.trim();
            if (!fn) errors.push('First name is required.');
            else if (fn.length < 2) errors.push('First name must be at least 2 characters.');

            const eid = document.getElementById('empId').value.trim();
            if (!eid) errors.push('Employee ID is required.');
            else if (!/^\d{4}\.\d-\d{3}$/.test(eid)) errors.push('Employee ID format: ####.#-###');

            const desig = document.getElementById('empDesignation').value.trim();
            if (!desig) errors.push('Designation is required.');

            const sal = document.getElementById('empSalary').value.trim();
            if (!sal) errors.push('Salary is required.');
            else if (isNaN(parseFloat(sal))) errors.push('Salary must be a valid number.');

            const ph = document.getElementById('empPhilhealth').value.trim();
            if (ph && !naOk(ph) && !/^\d{2}-\d{9}-\d$/.test(ph)) errors.push('PhilHealth format: ##-#########-# (or N/A)');

            const tin = document.getElementById('empTin').value.trim();
            if (tin && !naOk(tin) && !/^\d{3}-\d{3}-\d{3}-\d{4}$/.test(tin)) errors.push('TIN format: ###-###-###-#### (or N/A)');

            const gsis = document.getElementById('empGsis').value.trim();
            if (gsis && !naOk(gsis) && gsis.length !== 12) errors.push('GSIS No. must be exactly 12 digits (or N/A).');

            const pag = document.getElementById('empPagibig').value.trim();
            if (pag && !naOk(pag) && pag.length !== 12) errors.push('Pag-IBIG No. must be exactly 12 digits (or N/A).');

            return errors;
        }

        saveBtn.addEventListener('click', function () {
            const errors = validateAll();
            if (errors.length > 0) { showAlert(errors.join('<br>'), 'error'); return; }

            const name = document.getElementById('empFirstName').value.trim()
                       + ' ' + document.getElementById('empLastName').value.trim();
            modalBody.innerHTML =
                'You are about to save a new employee record for<br>' +
                '<strong>' + name + '</strong>.<br><br>' +
                'Department: <strong>PGSO</strong> &nbsp;|&nbsp; Standing: <strong>Regular</strong><br><br>Continue?';
            overlay.classList.add('active');
        });

        btnCancel.addEventListener('click',  () => overlay.classList.remove('active'));
        btnConfirm.addEventListener('click', () => { overlay.classList.remove('active'); form.submit(); });
        overlay.addEventListener('click',    e  => { if (e.target === overlay) overlay.classList.remove('active'); });
    })();

})();