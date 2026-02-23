(function () {
    if (document.getElementById('joregPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'joregPopupStyle';
    s.textContent =
        '@keyframes joFadeIn { from { opacity:0; transform:scale(0.96); }' +
                             '  to   { opacity:1; transform:scale(1);    } }';
    document.head.appendChild(s);
})();

function showConfirm(message, onConfirm) {
    const existing = document.getElementById('joregConfirmOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'joregConfirmOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:420px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;animation:joFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2rem;margin-bottom:10px;">💾</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:24px;">' +
            message +
        '</p>' +
        '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button id="joregCancelBtn"' +
            ' style="padding:9px 26px;background:#e8f0e8;color:#1a3d1f;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Cancel</button>' +
            '<button id="joregConfirmBtn"' +
            ' style="padding:9px 26px;background:#2a5c30;color:#fff;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Confirm</button>' +
        '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
    document.getElementById('joregCancelBtn').addEventListener('click', function () {
        overlay.remove();
    });
    document.getElementById('joregConfirmBtn').addEventListener('click', function () {
        overlay.remove();
        onConfirm();
    });
}

function showAlert(msg, type) {
    const el = document.getElementById('joregAlert');
    if (!el) return;
    el.className   = 'joreg-alert ' + type;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(el._timeout);
    el._timeout = setTimeout(function () {
        el.className   = 'joreg-alert';
        el.textContent = '';
    }, 5000);
}

function validateNameInput(input) {
    const val   = input.value;
    const errEl = input.closest('.joreg-field-wrap')?.querySelector('.joreg-field-error');
    let msg = '';

    if (/[0-9]/.test(val)) {
        msg = 'Name must not contain numbers.';
    } else if (val.trim().length > 0 && val.trim().length < 5) {
        msg = 'Name must be at least 5 characters.';
    }

    if (errEl) {
        errEl.textContent   = msg;
        errEl.style.display = msg ? 'block' : 'none';
    }
    input.classList.toggle('joreg-input--error', !!msg);
    return msg === '';
}

function attachNameValidation(input) {
    /* Block number keys in real-time */
    input.addEventListener('keydown', function (e) {
        if (/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            const errEl = input.closest('.joreg-field-wrap')?.querySelector('.joreg-field-error');
            if (errEl) {
                errEl.textContent   = 'Name must not contain numbers.';
                errEl.style.display = 'block';
            }
            input.classList.add('joreg-input--error');
        }
    });
    input.addEventListener('input', function () { validateNameInput(this); });
    input.addEventListener('blur',  function () { validateNameInput(this); });
}

function validateRateInput(input) {
    const val   = input.value.trim();
    const errEl = input.closest('.joreg-field-wrap') 
                    ? input.closest('.joreg-field-wrap').querySelector('.joreg-field-error')
                    : null;
    let msg = '';

    if (val === '') {
        msg = 'Rate is required.';
    } else if (!/^\d+(\.\d{0,2})?$/.test(val)) {
        msg = 'Enter a valid number (e.g. 500 or 20.50).';
    } else if (parseFloat(val) <= 0) {
        msg = 'Rate must be greater than 0.';
    } else if (parseFloat(val) > 50000) {
        msg = 'Rate cannot exceed 50,000.';
    }

    if (errEl) {
        errEl.textContent   = msg;
        errEl.style.display = msg ? 'block' : 'none';
    }
    input.classList.toggle('joreg-input--error', !!msg);
    return msg === '';
}

function attachRateValidation(input) {
    /* Only allow digits and a single decimal point */
    input.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
        if (allowed.includes(e.key)) return;

        /* Allow one dot only if none exists yet */
        if (e.key === '.') {
            if (this.value.includes('.')) e.preventDefault();
            return;
        }

        /* Block anything that isn't a digit */
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            const errEl = this.closest('.joreg-field-wrap')
                            ? this.closest('.joreg-field-wrap').querySelector('.joreg-field-error')
                            : null;
            if (errEl) {
                errEl.textContent   = 'Only numbers are allowed.';
                errEl.style.display = 'block';
            }
            this.classList.add('joreg-input--error');
        }
    });

    /* Limit to 2 decimal places on paste */
    input.addEventListener('input', function () {
        if (/^\d+\.\d{3,}$/.test(this.value)) {
            this.value = parseFloat(this.value).toFixed(2);
        }
        validateRateInput(this);
    });

    input.addEventListener('blur', function () { validateRateInput(this); });
}

function initDateLogic() {
    const dateFrom = document.getElementById('joregDateFrom');
    const dateTo   = document.getElementById('joregDateTo');
    if (!dateFrom || !dateTo) return;

    function getNextDay(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        return d.toISOString().split('T')[0];
    }

    dateFrom.addEventListener('change', function () {
        const from = this.value;
        if (!from) return;

        const minTo = getNextDay(from);
        dateTo.min = minTo;

        /* If the currently selected TO date is now before the new minimum, clear it */
        if (dateTo.value && dateTo.value <= from) {
            dateTo.value = '';
        }

        /* Auto-focus TO so the user continues naturally */
        dateTo.focus();
    });

    /* Also enforce: TO cannot be same or before FROM on direct change */
    dateTo.addEventListener('change', function () {
        const from = dateFrom.value;
        if (!from || !this.value) return;

        if (this.value <= from) {
            this.value = '';
            showAlert('The "To" date must be after the "From" date.', 'error');
            this.focus();
        }
    });
}

function attachAutocomplete(input) {
    const listKey = input.dataset.list;
    const options = (listKey === 'DESIGNATION_LIST' ? DESIGNATION_LIST : FUNDING_CHARGES_LIST)
                        .map(function (v) { return v.toUpperCase(); });

    const wrap  = input.closest('.joreg-ac-wrap');
    const ul    = wrap.querySelector('.joreg-ac-dropdown');
    const errEl = wrap.querySelector('.joreg-field-error');

    function showError(msg) {
        if (!errEl) return;
        errEl.textContent   = msg;
        errEl.style.display = msg ? 'block' : 'none';
        input.classList.toggle('joreg-input--error', !!msg);
    }

    function openDropdown(filtered) {
        ul.innerHTML = '';
        if (!filtered.length) { ul.style.display = 'none'; return; }
        filtered.forEach(function (val) {
            const li = document.createElement('li');
            li.textContent = val;
            li.setAttribute('data-val', val);
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = val;
                showError('');
                closeDropdown();
            });
            ul.appendChild(li);
        });
        ul.style.display = 'block';
    }

    function closeDropdown() {
        ul.style.display = 'none';
        ul.innerHTML = '';
    }

    input.addEventListener('input', function () {
        showError('');
        const q = this.value.trim().toUpperCase();
        if (!q) { closeDropdown(); return; }
        openDropdown(options.filter(function (o) { return o.includes(q); }));
    });

    input.addEventListener('focus', function () {
        const q = this.value.trim().toUpperCase();
        openDropdown(q ? options.filter(function (o) { return o.includes(q); }) : options);
    });

    input.addEventListener('keydown', function (e) {
        const items = ul.querySelectorAll('li');
        if (!items.length) return;
        let activeIndex = Array.from(items).findIndex(function (li) {
            return li.classList.contains('active');
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            input.value = items[activeIndex].textContent;
            showError('');
            closeDropdown();
            return;
        } else if (e.key === 'Escape') {
            closeDropdown(); return;
        } else { return; }

        items.forEach(function (li, i) { li.classList.toggle('active', i === activeIndex); });
        if (activeIndex >= 0) items[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    input.addEventListener('blur', function () {
        setTimeout(function () {
            closeDropdown();
            const typed = input.value.trim().toUpperCase();
            if (!typed) return;
            if (options.some(function (o) { return o === typed; })) {
                input.value = typed;
                showError('');
            } else {
                input.value = '';
                showError('Please select a valid option from the list.');
            }
        }, 160);
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) closeDropdown();
    });
}

function attachRateValidationToRow(row) {
    /* Rate inputs don't have a joreg-field-wrap by default — wrap them */
    const rateInputs = row.querySelectorAll('input[name="rate[]"]');
    rateInputs.forEach(function (input) {
        /* Wrap in joreg-field-wrap if not already wrapped */
        if (!input.closest('.joreg-field-wrap')) {
            const wrap = document.createElement('div');
            wrap.className = 'joreg-field-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            const errSpan = document.createElement('span');
            errSpan.className = 'joreg-field-error';
            wrap.appendChild(errSpan);
        }
        attachRateValidation(input);
    });
}


(function () {

    const rowsContainer  = document.getElementById('joregRows');
    const pdfInput       = document.getElementById('joregPdfFile');
    const pdfNameEl      = document.getElementById('joregPdfName');
    const refFolderInput = document.getElementById('joregRefFolder');

    /* Init first row */
    rowsContainer.querySelectorAll('.joreg-name-input').forEach(attachNameValidation);
    rowsContainer.querySelectorAll('.joreg-ac-input').forEach(attachAutocomplete);
    rowsContainer.querySelectorAll('.joreg-entry-row').forEach(attachRateValidationToRow);

    /* Init date logic */
    initDateLogic();

    /* ── PDF selected → auto-fill Reference Folder ── */
    if (pdfInput) {
        pdfInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const baseName = fileName.replace(/\.[^/.]+$/, '');
                pdfNameEl.textContent = '📎 ' + fileName;
                if (refFolderInput && refFolderInput.value.trim() === '') {
                    refFolderInput.value = baseName.toUpperCase();
                }
            } else {
                pdfNameEl.textContent = '';
            }
        });
    }

    /* ── Add new entry row ── */
    window.addEntryRow = function () {
        const row = document.createElement('div');
        row.className = 'joreg-entry-row';
        row.innerHTML =
            '<div class="joreg-field-wrap">' +
                '<input type="text" class="joreg-input joreg-name-input" name="name[]"' +
                '       placeholder="Full name" autocomplete="off" required>' +
                '<span class="joreg-field-error"></span>' +
            '</div>' +
            '<div class="joreg-field-wrap joreg-ac-wrap">' +
                '<input type="text" class="joreg-input joreg-ac-input" name="designation[]"' +
                '       placeholder="Designation" data-list="DESIGNATION_LIST"' +
                '       autocomplete="off" required>' +
                '<span class="joreg-field-error"></span>' +
                '<ul class="joreg-ac-dropdown"></ul>' +
            '</div>' +
            '<div class="joreg-field-wrap joreg-ac-wrap">' +
                '<input type="text" class="joreg-input joreg-ac-input" name="funding_charges[]"' +
                '       placeholder="Funding charges" data-list="FUNDING_CHARGES_LIST"' +
                '       autocomplete="off" required>' +
                '<span class="joreg-field-error"></span>' +
                '<ul class="joreg-ac-dropdown"></ul>' +
            '</div>' +
            /* Rate already wrapped in joreg-field-wrap for error display */
            '<div class="joreg-field-wrap">' +
                '<input type="text" class="joreg-input" name="rate[]" placeholder="0.00" required>' +
                '<span class="joreg-field-error"></span>' +
            '</div>' +
            '<button type="button" class="joreg-btn-remove-row"' +
            '        onclick="removeRow(this)" title="Remove row">×</button>';

        rowsContainer.appendChild(row);
        row.querySelectorAll('.joreg-name-input').forEach(attachNameValidation);
        row.querySelectorAll('.joreg-ac-input').forEach(attachAutocomplete);
        row.querySelectorAll('input[name="rate[]"]').forEach(attachRateValidation);
    };

    /* ── Remove entry row ── */
    window.removeRow = function (btn) {
        const row     = btn.closest('.joreg-entry-row');
        const allRows = rowsContainer.querySelectorAll('.joreg-entry-row');
        if (allRows.length <= 1) {
            showAlert('At least one entry row is required.', 'error');
            return;
        }
        row.style.animation = 'rowSlideIn 0.15s ease reverse';
        setTimeout(function () { row.remove(); }, 140);
    };

    /* ── Form submit ── */
    const form = document.getElementById('joregForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const dateFrom  = document.getElementById('joregDateFrom').value.trim();
        const dateTo    = document.getElementById('joregDateTo').value.trim();
        const refFolder = refFolderInput ? refFolderInput.value.trim() : '';

        if (!dateFrom || !dateTo) {
            showAlert('Please fill in both FROM and TO dates.', 'error');
            return;
        }

        if (dateTo <= dateFrom) {
            showAlert('The "To" date must be after the "From" date.', 'error');
            document.getElementById('joregDateTo').focus();
            return;
        }

        if (!refFolder) {
            showAlert('Please enter a Reference Folder name or upload a PDF.', 'error');
            return;
        }

        /* Validate name inputs */
        let nameOk = true;
        rowsContainer.querySelectorAll('.joreg-name-input').forEach(function (inp) {
            if (!validateNameInput(inp)) nameOk = false;
        });
        if (!nameOk) {
            showAlert('Please fix the name errors before saving.', 'error');
            return;
        }

        /* Validate rate inputs */
        let rateOk = true;
        rowsContainer.querySelectorAll('input[name="rate[]"]').forEach(function (inp) {
            if (!validateRateInput(inp)) rateOk = false;
        });
        if (!rateOk) {
            showAlert('Please enter valid rate values (numbers only).', 'error');
            return;
        }

        /* Validate autocomplete inputs */
        let acOk = true;
        rowsContainer.querySelectorAll('.joreg-ac-input').forEach(function (inp) {
            const listKey = inp.dataset.list;
            const options = (listKey === 'DESIGNATION_LIST' ? DESIGNATION_LIST : FUNDING_CHARGES_LIST)
                                .map(function (v) { return v.toUpperCase(); });
            const val = inp.value.trim().toUpperCase();
            if (!val || !options.some(function (o) { return o === val; })) {
                acOk = false;
                const errEl = inp.closest('.joreg-ac-wrap')?.querySelector('.joreg-field-error');
                if (errEl) {
                    errEl.textContent   = 'Please select a valid option from the list.';
                    errEl.style.display = 'block';
                }
                inp.classList.add('joreg-input--error');
            }
        });
        if (!acOk) {
            showAlert('Please fix the highlighted fields before saving.', 'error');
            return;
        }

        /* Check all other required fields */
        let allFilled = true;
        rowsContainer.querySelectorAll('input[required]').forEach(function (el) {
            if (el.value.trim() === '') allFilled = false;
        });
        if (!allFilled) {
            showAlert('Please fill in all fields in every row.', 'error');
            return;
        }

        const totalRows = rowsContainer.querySelectorAll('.joreg-entry-row').length;
        const hasPdf    = pdfInput && pdfInput.files && pdfInput.files.length > 0;

        showConfirm(
            'You are about to save <strong>' + totalRows + '</strong> record(s).<br>' +
            '<span style="font-size:0.82rem;color:#7a9e7e;">' +
                'PDF: ' + (hasPdf
                    ? '✅ Attached (existing file will be replaced if same name)'
                    : '❌ None — will show as <em>Unavailable</em>') +
            '</span><br><br>Proceed?',
            function () { form.submit(); }
        );
    });

})();