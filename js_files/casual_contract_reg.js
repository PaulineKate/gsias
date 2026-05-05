(function () {
    if (document.getElementById('cascPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'cascPopupStyle';
    s.textContent =
        '@keyframes cascFadeIn { from { opacity:0; transform:scale(0.96); }' +
                              '  to   { opacity:1; transform:scale(1);    } }';
    document.head.appendChild(s);
})();

/* ═══════════════════════════════════════════════════════
   AJAX — fetch position titles from the server once
   and populate the global POSITION_TITLE_LIST
   ═══════════════════════════════════════════════════════ */
(function fetchPositionTitles() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_position_titles.php', true);
    xhr.responseType = 'json';

    xhr.onload = function () {
        var loadingInputs = document.querySelectorAll('.casc-ac-loading');

        if (xhr.status === 200 && xhr.response && xhr.response.success) {
            /* Build the global list from {id, p_name} rows */
            window.POSITION_TITLE_LIST = xhr.response.data.map(function (row) {
                return row.p_name;
            });

            /* Un-shimmer every autocomplete input already in the DOM */
            loadingInputs.forEach(function (inp) {
                inp.classList.remove('casc-ac-loading');
                inp.placeholder = 'Position title';
                inp.disabled    = false;
                /* Re-attach autocomplete now that the list is ready */
                attachAutocomplete(inp);
            });
        } else {
            /* Graceful degradation: allow free text, show a warning */
            loadingInputs.forEach(function (inp) {
                inp.classList.remove('casc-ac-loading');
                inp.placeholder = 'Position title (offline)';
                inp.disabled    = false;
            });
            console.warn('Could not load position titles from server.');
        }
    };

    xhr.onerror = function () {
        document.querySelectorAll('.casc-ac-loading').forEach(function (inp) {
            inp.classList.remove('casc-ac-loading');
            inp.placeholder = 'Position title (offline)';
            inp.disabled    = false;
        });
        console.warn('Network error while loading position titles.');
    };

    xhr.send();
})();

/* ═══════════════════════════════════════════════════════
   UI helpers
   ═══════════════════════════════════════════════════════ */
function showConfirm(message, onConfirm) {
    const existing = document.getElementById('cascConfirmOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'cascConfirmOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:420px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;animation:cascFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2rem;margin-bottom:10px;">💾</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:24px;">' +
            message +
        '</p>' +
        '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button id="cascCancelBtn"' +
            ' style="padding:9px 26px;background:#e8f0e8;color:#1a3d1f;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Cancel</button>' +
            '<button id="cascConfirmBtn"' +
            ' style="padding:9px 26px;background:#2a5c30;color:#fff;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Confirm</button>' +
        '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.remove(); });
    document.getElementById('cascCancelBtn').addEventListener('click', function () { overlay.remove(); });
    document.getElementById('cascConfirmBtn').addEventListener('click', function () {
        overlay.remove();
        onConfirm();
    });
}

function showAlert(msg, type) {
    const el = document.getElementById('cascAlert');
    if (!el) return;
    el.className   = 'casc-alert ' + type;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(el._timeout);
    el._timeout = setTimeout(function () {
        el.className   = 'casc-alert';
        el.textContent = '';
    }, 5000);
}

/* ═══════════════════════════════════════════════════════
   Field validators
   ═══════════════════════════════════════════════════════ */
function validateNameInput(input) {
    const val   = input.value;
    const errEl = input.closest('.casc-field-wrap') &&
                  input.closest('.casc-field-wrap').querySelector('.casc-field-error');
    let msg = '';

    if (/[0-9]/.test(val)) {
        msg = 'Must not contain numbers.';
    } else if (val.trim().length > 0 && val.trim().length < 2) {
        msg = 'Must be at least 2 characters.';
    }

    if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
    input.classList.toggle('casc-input--error', !!msg);
    return msg === '';
}

function attachNameValidation(input) {
    input.addEventListener('keydown', function (e) {
        if (/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            const errEl = input.closest('.casc-field-wrap') &&
                          input.closest('.casc-field-wrap').querySelector('.casc-field-error');
            if (errEl) { errEl.textContent = 'Must not contain numbers.'; errEl.style.display = 'block'; }
            input.classList.add('casc-input--error');
        }
    });
    input.addEventListener('input', function () { validateNameInput(this); });
    input.addEventListener('blur',  function () { validateNameInput(this); });
}

function attachExtensionValidation(input) {
    input.addEventListener('blur', function () {
        const val   = this.value.trim();
        const errEl = this.closest('.casc-field-wrap') &&
                      this.closest('.casc-field-wrap').querySelector('.casc-field-error');
        let msg = '';
        if (val !== '' && !/^[A-Za-z.\-]+$/.test(val)) msg = '';
        if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
        this.classList.toggle('casc-input--error', !!msg);
    });
}

function validatePayGrade(input) {
    const val   = parseInt(input.value, 10);
    const errEl = input.closest('.casc-field-wrap') &&
                  input.closest('.casc-field-wrap').querySelector('.casc-field-error');
    let msg = '';
    if (input.value.trim() === '')             msg = 'Pay grade is required.';
    else if (isNaN(val) || val < 1 || val > 10) msg = 'Must be between 1 and 10.';
    if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
    input.classList.toggle('casc-input--error', !!msg);
    return msg === '';
}

function attachPayGradeValidation(input) {
    input.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
        if (allowed.includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    });
    input.addEventListener('input', function () {
        const v = parseInt(this.value, 10);
        if (!isNaN(v) && v > 10) this.value = '10';
        if (!isNaN(v) && v < 1 && this.value.length > 0) this.value = '';
        validatePayGrade(this);
    });
    input.addEventListener('blur', function () { validatePayGrade(this); });
}

function validateDailyWage(input) {
    const val   = input.value.trim();
    const errEl = input.closest('.casc-field-wrap') &&
                  input.closest('.casc-field-wrap').querySelector('.casc-field-error');
    let msg = '';
    if (val === '')                              msg = 'Daily wage is required.';
    else if (!/^\d+(\.\d{0,2})?$/.test(val))   msg = 'Enter a valid number (e.g. 500 or 550.50).';
    else if (parseFloat(val) <= 0)              msg = 'Must be greater than 0.';
    else if (parseFloat(val) > 999999)          msg = 'Value seems too large.';
    if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
    input.classList.toggle('casc-input--error', !!msg);
    return msg === '';
}

function attachDailyWageValidation(input) {
    input.addEventListener('keydown', function (e) {
        const allowed = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'];
        if (allowed.includes(e.key)) return;
        if (e.key === '.') { if (this.value.includes('.')) e.preventDefault(); return; }
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            const errEl = this.closest('.casc-field-wrap') &&
                          this.closest('.casc-field-wrap').querySelector('.casc-field-error');
            if (errEl) { errEl.textContent = 'Only numbers are allowed.'; errEl.style.display = 'block'; }
            this.classList.add('casc-input--error');
        }
    });
    input.addEventListener('input', function () {
        if (/^\d+\.\d{3,}$/.test(this.value)) this.value = parseFloat(this.value).toFixed(2);
        validateDailyWage(this);
    });
    input.addEventListener('blur', function () { validateDailyWage(this); });
}

function validateApptNature(select) {
    const errEl = select.closest('.casc-field-wrap') &&
                  select.closest('.casc-field-wrap').querySelector('.casc-field-error');
    const msg = select.value === '' ? 'Please select a nature of appointment.' : '';
    if (errEl) { errEl.textContent = msg; errEl.style.display = msg ? 'block' : 'none'; }
    select.classList.toggle('casc-select--error', !!msg);
    return msg === '';
}

function attachApptNatureValidation(select) {
    select.addEventListener('change', function () { validateApptNature(this); });
    select.addEventListener('blur',   function () { validateApptNature(this); });
}

/* ═══════════════════════════════════════════════════════
   Autocomplete — uses window.POSITION_TITLE_LIST
   (populated by AJAX; safe to call before AJAX completes
   because the list will just be empty until it arrives)
   ═══════════════════════════════════════════════════════ */
function attachAutocomplete(input) {
    /* Skip if still loading */
    if (input.classList.contains('casc-ac-loading')) return;

    const wrap  = input.closest('.casc-ac-wrap');
    const ul    = wrap.querySelector('.casc-ac-dropdown');
    const errEl = wrap.querySelector('.casc-field-error');

    function getOptions() {
        return (window.POSITION_TITLE_LIST || []).map(function (v) {
            return v.toUpperCase();
        });
    }

    function showError(msg) {
        if (!errEl) return;
        errEl.textContent   = msg;
        errEl.style.display = msg ? 'block' : 'none';
        input.classList.toggle('casc-input--error', !!msg);
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
        openDropdown(getOptions().filter(function (o) { return o.includes(q); }));
    });

    input.addEventListener('focus', function () {
        const q = this.value.trim().toUpperCase();
        openDropdown(q ? getOptions().filter(function (o) { return o.includes(q); }) : getOptions());
    });

    input.addEventListener('keydown', function (e) {
        const items = ul.querySelectorAll('li');
        if (!items.length) return;
        let activeIndex = Array.from(items).findIndex(function (li) {
            return li.classList.contains('active');
        });

        if (e.key === 'ArrowDown')      { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, items.length - 1); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); }
        else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            input.value = items[activeIndex].textContent;
            showError('');
            closeDropdown();
            return;
        } else if (e.key === 'Escape') { closeDropdown(); return; }
        else { return; }

        items.forEach(function (li, i) { li.classList.toggle('active', i === activeIndex); });
        if (activeIndex >= 0) items[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    input.addEventListener('blur', function () {
        setTimeout(function () {
            closeDropdown();
            const typed = input.value.trim().toUpperCase();
            if (!typed) return;
            if (getOptions().some(function (o) { return o === typed; })) {
                input.value = typed;
                showError('');
            } else {
                input.value = '';
                showError('Please select a valid position title from the list.');
            }
        }, 160);
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) closeDropdown();
    });
}

/* ═══════════════════════════════════════════════════════
   Date logic
   ═══════════════════════════════════════════════════════ */
function initDateLogic() {
    const dateFrom = document.getElementById('cascDateFrom');
    const dateTo   = document.getElementById('cascDateTo');
    if (!dateFrom || !dateTo) return;

    function getNextDay(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        return d.toISOString().split('T')[0];
    }

    dateFrom.addEventListener('change', function () {
        const from = this.value;
        if (!from) return;
        dateTo.min = getNextDay(from);
        if (dateTo.value && dateTo.value <= from) dateTo.value = '';
        dateTo.focus();
    });

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

/* ═══════════════════════════════════════════════════════
   Row builder
   ═══════════════════════════════════════════════════════ */
function buildRowHTML(isFirst) {
    const btn = isFirst
        ? '<button type="button" class="casc-btn-add-row" onclick="cascAddEntryRow()" title="Add another row">+</button>'
        : '<button type="button" class="casc-btn-remove-row" onclick="cascRemoveRow(this)" title="Remove row">×</button>';

    return (
        '<div class="casc-field-wrap">' +
            '<input type="text" class="casc-input casc-name-input" name="last_name[]"' +
            '       placeholder="Last name" autocomplete="off" required>' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<input type="text" class="casc-input casc-name-input" name="first_name[]"' +
            '       placeholder="First name" autocomplete="off" required>' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<input type="text" class="casc-input casc-ext-input" name="name_extension[]"' +
            '       placeholder="N/A" value="N/A" maxlength="5" autocomplete="off">' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<input type="text" class="casc-input casc-name-input" name="middle_name[]"' +
            '       placeholder="Middle name" autocomplete="off">' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap casc-ac-wrap">' +
            '<input type="text" class="casc-input casc-ac-input" name="position_title[]"' +
            '       placeholder="Position title" autocomplete="off" required>' +
            '<span class="casc-field-error"></span>' +
            '<ul class="casc-ac-dropdown"></ul>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<input type="number" class="casc-input casc-paygrade-input" name="pay_grade[]"' +
            '       placeholder="1–10" min="1" max="10" required>' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<input type="text" class="casc-input casc-wage-input" name="daily_wage[]"' +
            '       placeholder="0.00" required>' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        '<div class="casc-field-wrap">' +
            '<select class="casc-select casc-appt-select" name="appointment_nature[]" required>' +
                '<option value="">— Select —</option>' +
                '<option value="Original">Original</option>' +
                '<option value="Reappointment">Reappointment</option>' +
                '<option value="Reemployment">Reemployment</option>' +
            '</select>' +
            '<span class="casc-field-error"></span>' +
        '</div>' +
        btn
    );
}

function initRow(row) {
    row.querySelectorAll('.casc-name-input').forEach(attachNameValidation);
    row.querySelectorAll('.casc-ext-input').forEach(attachExtensionValidation);
    /* Only attach autocomplete if the list has already loaded */
    row.querySelectorAll('.casc-ac-input:not(.casc-ac-loading)').forEach(attachAutocomplete);
    row.querySelectorAll('.casc-paygrade-input').forEach(attachPayGradeValidation);
    row.querySelectorAll('.casc-wage-input').forEach(attachDailyWageValidation);
    row.querySelectorAll('.casc-appt-select').forEach(attachApptNatureValidation);
}

/* ═══════════════════════════════════════════════════════
   Bootstrap
   ═══════════════════════════════════════════════════════ */
(function () {
    const rowsContainer  = document.getElementById('cascRows');
    const pdfInput       = document.getElementById('cascPdfFile');
    const pdfNameEl      = document.getElementById('cascPdfName');
    const refFolderInput = document.getElementById('cascRefFolder');
    const bulkCountInput = document.getElementById('cascBulkCount');

    /* Init first row (autocomplete will be wired up after AJAX completes) */
    if (rowsContainer) {
        rowsContainer.querySelectorAll('.casc-entry-row').forEach(initRow);
    }

    initDateLogic();

    /* PDF → auto-fill Reference Folder */
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

    /* ── Add single row ── */
    window.cascAddEntryRow = function () {
        const row = document.createElement('div');
        row.className = 'casc-entry-row';
        row.innerHTML = buildRowHTML(false);
        rowsContainer.appendChild(row);
        initRow(row);
        row.querySelector('input').focus();
    };

    /* ── Bulk-add rows ── */
    window.cascBulkAdd = function () {
        const count = parseInt((bulkCountInput && bulkCountInput.value) || '1', 10);
        if (isNaN(count) || count < 1) {
            showAlert('Please enter a valid number of rows to add (minimum 1).', 'error');
            return;
        }
        if (count > 50) {
            showAlert('You can add at most 50 rows at a time.', 'error');
            return;
        }
        for (let i = 0; i < count; i++) {
            const row = document.createElement('div');
            row.className = 'casc-entry-row';
            row.innerHTML = buildRowHTML(false);
            rowsContainer.appendChild(row);
            initRow(row);
        }
        rowsContainer.lastElementChild.querySelector('input').focus();
    };

    /* ── Remove row ── */
    window.cascRemoveRow = function (btn) {
        const row     = btn.closest('.casc-entry-row');
        const allRows = rowsContainer.querySelectorAll('.casc-entry-row');
        if (allRows.length <= 1) {
            showAlert('At least one entry row is required.', 'error');
            return;
        }
        row.style.animation = 'cascRowSlideIn 0.15s ease reverse';
        setTimeout(function () { row.remove(); }, 140);
    };

    /* ── Form submit ── */
    const form = document.getElementById('cascForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const dateFrom  = document.getElementById('cascDateFrom').value.trim();
        const dateTo    = document.getElementById('cascDateTo').value.trim();
        const refFolder = refFolderInput ? refFolderInput.value.trim() : '';

        if (!dateFrom || !dateTo) {
            showAlert('Please fill in both FROM and TO dates.', 'error');
            return;
        }
        if (dateTo <= dateFrom) {
            showAlert('The "To" date must be after the "From" date.', 'error');
            document.getElementById('cascDateTo').focus();
            return;
        }
        if (!refFolder) {
            showAlert('Please enter a Reference Folder name or upload a PDF.', 'error');
            return;
        }

        /* Guard: list must have loaded before submitting */
        if (!window.POSITION_TITLE_LIST || window.POSITION_TITLE_LIST.length === 0) {
            showAlert('Position title list is still loading. Please wait a moment and try again.', 'error');
            return;
        }

        let allValid = true;

        rowsContainer.querySelectorAll('.casc-name-input').forEach(function (inp) {
            const isRequired = (inp.name === 'first_name[]' || inp.name === 'last_name[]');
            if (isRequired && inp.value.trim() === '') {
                const errEl = inp.closest('.casc-field-wrap') &&
                              inp.closest('.casc-field-wrap').querySelector('.casc-field-error');
                if (errEl) { errEl.textContent = 'This field is required.'; errEl.style.display = 'block'; }
                inp.classList.add('casc-input--error');
                allValid = false;
            } else {
                if (!validateNameInput(inp)) allValid = false;
            }
        });

        rowsContainer.querySelectorAll('.casc-ext-input').forEach(function (inp) {
            const val   = inp.value.trim();
            const errEl = inp.closest('.casc-field-wrap') &&
                          inp.closest('.casc-field-wrap').querySelector('.casc-field-error');
            if (val !== '' && val !== 'N/A' && !/^[A-Za-z.\-]+$/.test(val)) {
                if (errEl) { errEl.textContent = 'Only letters, dots, or hyphens allowed.'; errEl.style.display = 'block'; }
                inp.classList.add('casc-input--error');
                allValid = false;
            }
        });

        rowsContainer.querySelectorAll('.casc-ac-input').forEach(function (inp) {
            const options = (window.POSITION_TITLE_LIST || []).map(function (v) { return v.toUpperCase(); });
            const val     = inp.value.trim().toUpperCase();
            const errEl   = inp.closest('.casc-ac-wrap') &&
                            inp.closest('.casc-ac-wrap').querySelector('.casc-field-error');
            if (!val || !options.some(function (o) { return o === val; })) {
                if (errEl) { errEl.textContent = 'Please select a valid position title from the list.'; errEl.style.display = 'block'; }
                inp.classList.add('casc-input--error');
                allValid = false;
            }
        });

        rowsContainer.querySelectorAll('.casc-paygrade-input').forEach(function (inp) {
            if (!validatePayGrade(inp)) allValid = false;
        });

        rowsContainer.querySelectorAll('.casc-wage-input').forEach(function (inp) {
            if (!validateDailyWage(inp)) allValid = false;
        });

        rowsContainer.querySelectorAll('.casc-appt-select').forEach(function (sel) {
            if (!validateApptNature(sel)) allValid = false;
        });

        if (!allValid) {
            showAlert('Please fix the highlighted errors before saving.', 'error');
            return;
        }

        const totalRows = rowsContainer.querySelectorAll('.casc-entry-row').length;
        const hasPdf    = pdfInput && pdfInput.files && pdfInput.files.length > 0;

        showConfirm(
            'You are about to save <strong>' + totalRows + '</strong> record(s).<br>' +
            '<span style="font-size:0.82rem;color:#7a9e7e;">' +
                'PDF: ' + (hasPdf
                    ? 'Attached (existing file will be replaced if same name)'
                    : 'None — will show as <em>Unavailable</em>') +
            '</span><br><br>Proceed?',
            function () { form.submit(); }
        );
    });

})();