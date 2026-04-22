(function () {

    /* ── PHP-injected modal state on page load ── */
    if (PHP_MODAL.type) {
        if (PHP_MODAL.type === 'success') {
            showResultModal('✅', 'Success', PHP_MODAL.message, function () {
                if (PHP_MODAL.isDelete) {
                    window.location.href = 'regular_employees.php';
                }
            });
        } else {
            showResultModal('❌', 'Error', PHP_MODAL.message, null);
        }
    }

    /* ── Result modal helper ── */
    function showResultModal(icon, title, body, onClose) {
        const overlay = document.getElementById('empOverlay');
        document.getElementById('empModalIcon').textContent  = icon;
        document.getElementById('empModalTitle').textContent = title;
        document.getElementById('empModalBody').innerHTML    = body;
        overlay.classList.add('active');

        document.getElementById('empModalClose').onclick = function () {
            overlay.classList.remove('active');
            if (typeof onClose === 'function') onClose();
        };
    }

    /* ── Salary: digits + 2 decimal places only ── */
    (function () {
        const inp = document.getElementById('empSalary');
        if (!inp) return;

        inp.addEventListener('keydown', function (e) {
            const nav = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
            if (nav.includes(e.key)) return;
            if (/^[0-9]$/.test(e.key)) return;
            if (e.key === '.' && !this.value.includes('.')) return;
            e.preventDefault();
            showFieldErr('errSalary', 'Only numbers are allowed.');
            inp.classList.add('emp-input--error');
        });

        inp.addEventListener('input', function () {
            if (/^\d+\.\d{3,}$/.test(this.value)) this.value = parseFloat(this.value).toFixed(2);
            const msg = this.value.trim() === '' ? 'Salary is required.' : '';
            showFieldErr('errSalary', msg);
            this.classList.toggle('emp-input--error', !!msg);
        });
    })();

    function showFieldErr(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.style.display = msg ? 'block' : 'none';
    }

    /* ── Designation autocomplete ── */
    (function () {
        const inp   = document.getElementById('empDesignation');
        const ul    = document.getElementById('empDesigDropdown');
        const errEl = document.getElementById('errDesignation');
        let options = [];
        let loaded  = false;

        function loadDesignations(cb) {
            if (loaded) { cb(options); return; }
            fetch('employee_credentials.php?ajax=get_designations')
                .then(r => r.json())
                .then(data => {
                    options = Array.isArray(data) ? data.map(v => v.toUpperCase()) : [];
                    loaded = true;
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
                li.className = 'empty';
                li.textContent = 'No matches found';
                ul.appendChild(li);
            } else {
                filtered.forEach(val => {
                    const li = document.createElement('li');
                    li.textContent = val;
                    li.addEventListener('mousedown', e => {
                        e.preventDefault();
                        inp.value = val;
                        showErr('');
                        closeDd();
                    });
                    ul.appendChild(li);
                });
            }
            ul.style.display = 'block';
        }

        function closeDd() { ul.style.display = 'none'; ul.innerHTML = ''; }

        inp.addEventListener('focus', function () {
            loadDesignations(opts => {
                const q = this.value.trim().toUpperCase();
                openDd(q ? opts.filter(o => o.includes(q)) : opts);
            });
        });

        inp.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
            showErr('');
            loadDesignations(opts => {
                const q = this.value.trim();
                openDd(q ? opts.filter(o => o.includes(q)) : opts);
            });
        });

        inp.addEventListener('keydown', function (e) {
            const items = ul.querySelectorAll('li:not(.empty)');
            let idx = Array.from(items).findIndex(li => li.classList.contains('active'));
            if (e.key === 'ArrowDown')      { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp')   { e.preventDefault(); idx = Math.max(idx - 1, 0); }
            else if ((e.key === 'Enter' || e.key === 'Tab') && idx >= 0) {
                e.preventDefault(); inp.value = items[idx].textContent; showErr(''); closeDd(); return;
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
                    if (opts.some(o => o === typed)) { inp.value = typed; showErr(''); }
                    else { inp.value = ''; showErr('Please select a valid designation from the list.'); }
                });
            }, 160);
        });

        document.addEventListener('click', e => {
            if (!inp.closest('.emp-ac-wrap')?.contains(e.target)) closeDd();
        });
    })();

    /* ── Status toggle — saves immediately via AJAX ── */
    (function () {
        const toggle = document.getElementById('empStatusToggle');
        const label  = document.getElementById('statusLabel');
        if (!toggle) return;

        toggle.addEventListener('change', function () {
            const newStatus = this.checked ? 1 : 0;
            label.textContent = newStatus ? 'Active' : 'Inactive';

            fetch('employee_credentials.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax=toggle_status&emp_id=' + encodeURIComponent(EMP_ID) +
                      '&new_status=' + newStatus
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showResultModal(
                        newStatus ? '✅' : '⛔',
                        'Status Updated',
                        'Employee status changed to <strong>' + (newStatus ? 'Active' : 'Inactive') + '</strong>.',
                        null
                    );
                } else {
                    /* revert on failure */
                    toggle.checked = !toggle.checked;
                    label.textContent = toggle.checked ? 'Active' : 'Inactive';
                    showResultModal('❌', 'Error', data.error || 'Could not update status.', null);
                }
            })
            .catch(() => {
                toggle.checked = !toggle.checked;
                label.textContent = toggle.checked ? 'Active' : 'Inactive';
                showResultModal('❌', 'Error', 'Network error. Please try again.', null);
            });
        });
    })();

    /* ── Update button → confirm modal → submit ── */
    (function () {
        const updateBtn      = document.getElementById('empUpdateBtn');
        const confirmOverlay = document.getElementById('confirmOverlay');
        const confirmCancel  = document.getElementById('confirmCancel');
        const confirmProceed = document.getElementById('confirmProceed');
        const form           = document.getElementById('empForm');
        const formAction     = document.getElementById('formAction');

        updateBtn.addEventListener('click', function () {
            /* Quick client-side check */
            const desig  = document.getElementById('empDesignation').value.trim();
            const salary = document.getElementById('empSalary').value.trim();
            if (!desig)  { showFieldErr('errDesignation', 'Designation is required.'); return; }
            if (!salary) { showFieldErr('errSalary', 'Salary is required.'); return; }

            formAction.value = 'update';
            confirmOverlay.classList.add('active');
        });

        confirmCancel.addEventListener('click',  () => confirmOverlay.classList.remove('active'));
        confirmProceed.addEventListener('click', () => {
            confirmOverlay.classList.remove('active');
            form.submit();
        });
        confirmOverlay.addEventListener('click', e => {
            if (e.target === confirmOverlay) confirmOverlay.classList.remove('active');
        });
    })();

    /* ── Delete button → confirm modal → submit ── */
    (function () {
        const deleteBtn     = document.getElementById('empDeleteBtn');
        const deleteOverlay = document.getElementById('deleteOverlay');
        const deleteCancel  = document.getElementById('deleteCancel');
        const deleteProceed = document.getElementById('deleteProceed');
        const form          = document.getElementById('empForm');
        const formAction    = document.getElementById('formAction');

        deleteBtn.addEventListener('click', () => deleteOverlay.classList.add('active'));

        deleteCancel.addEventListener('click',  () => deleteOverlay.classList.remove('active'));
        deleteProceed.addEventListener('click', () => {
            deleteOverlay.classList.remove('active');
            formAction.value = 'delete';
            form.submit();
        });
        deleteOverlay.addEventListener('click', e => {
            if (e.target === deleteOverlay) deleteOverlay.classList.remove('active');
        });
    })();

})();