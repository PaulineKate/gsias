/* general_settings.js
   Popup + validation pattern matches jo_contract_reg.js
*/

/* ══════════════════════════════════════════════
   0. FADE-IN KEYFRAME (injected once)
   ══════════════════════════════════════════════ */
(function () {
    if (document.getElementById('gsPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'gsPopupStyle';
    s.textContent =
        '@keyframes gsFadeIn { from { opacity:0; transform:scale(0.96); }' +
                             '  to   { opacity:1; transform:scale(1);    } }';
    document.head.appendChild(s);
})();


/* ══════════════════════════════════════════════
   1. CONFIRM POPUP  (matches joreg pattern)
   ══════════════════════════════════════════════ */
function showConfirm(message, onConfirm) {
    const existing = document.getElementById('gsConfirmOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'gsConfirmOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:420px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;animation:gsFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2rem;margin-bottom:10px;">⚠️</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:24px;">' +
            message +
        '</p>' +
        '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button id="gsCancelBtn"' +
            ' style="padding:9px 26px;background:#e8f0e8;color:#1a3d1f;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Cancel</button>' +
            '<button id="gsConfirmBtn"' +
            ' style="padding:9px 26px;background:#2a5c30;color:#fff;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Confirm</button>' +
        '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
    document.getElementById('gsCancelBtn').addEventListener('click', function () {
        overlay.remove();
    });
    document.getElementById('gsConfirmBtn').addEventListener('click', function () {
        overlay.remove();
        onConfirm();
    });
}


/* ══════════════════════════════════════════════
   2. INLINE ALERT  (matches joreg pattern)
   ══════════════════════════════════════════════ */
function showAlert(panelAlertId, msg, type) {
    const el = document.getElementById(panelAlertId);
    if (!el) return;
    el.className   = 'gs-alert ' + type;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(el._timeout);
    el._timeout = setTimeout(function () {
        el.className   = 'gs-alert';
        el.textContent = '';
    }, 5000);
}


/* ══════════════════════════════════════════════
   3. INPUT VALIDATION — no numbers, min 2 chars
   ══════════════════════════════════════════════ */
function validateInput(input, errorElId) {
    const val    = input.value.trim();
    const errEl  = document.getElementById(errorElId);
    let   msg    = '';

    if (val === '') {
        msg = 'This field cannot be empty.';
    } else if (/[0-9]/.test(val)) {
        msg = 'Numbers are not allowed.';
    } else if (val.length < 2) {
        msg = 'Must be at least 2 characters.';
    }

    if (errEl) {
        errEl.textContent   = msg;
        errEl.style.display = msg ? 'block' : 'none';
    }
    input.classList.toggle('gs-input--error', !!msg);
    return msg === '';
}

function attachInputValidation(inputId, errorElId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    /* Block number keys in real-time */
    input.addEventListener('keydown', function (e) {
        if (/^[0-9]$/.test(e.key)) {
            e.preventDefault();
            const errEl = document.getElementById(errorElId);
            if (errEl) {
                errEl.textContent   = 'Numbers are not allowed.';
                errEl.style.display = 'block';
            }
            input.classList.add('gs-input--error');
        }
    });

    input.addEventListener('input',  function () { validateInput(this, errorElId); });
    input.addEventListener('blur',   function () { validateInput(this, errorElId); });
}


/* ══════════════════════════════════════════════
   4. UTILITIES
   ══════════════════════════════════════════════ */
function renumberRows(tbodyId) {
    const rows = document.querySelectorAll('#' + tbodyId + ' tr:not(.gs-empty-row)');
    rows.forEach(function (row, idx) {
        row.cells[0].textContent = idx + 1;
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}


/* ══════════════════════════════════════════════
   5. SAVE (INSERT)
   ══════════════════════════════════════════════ */
function saveRecord(type) {
    const inputId      = type === 'designation' ? 'designation-input'       : 'funding-input';
    const errorElId    = type === 'designation' ? 'designation-input-error' : 'funding-input-error';
    const panelAlertId = type === 'designation' ? 'designation-alert'       : 'funding-alert';
    const tbodyId      = type === 'designation' ? 'designation-body'        : 'funding-body';
    const emptyId      = type === 'designation' ? 'designation-empty'       : 'funding-empty';
    const action       = type === 'designation' ? 'add_designation'         : 'add_funding';
    const label        = type === 'designation' ? 'designation'             : 'funding charge';

    const input = document.getElementById(inputId);
    const name  = input.value.trim().toUpperCase();

    /* Client-side validation before showing confirm */
    if (!validateInput(input, errorElId)) {
        input.focus();
        return;
    }

    showConfirm(
        'Add <strong>"' + escapeHtml(name) + '"</strong> as a new ' + label + '?',
        function () {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('name',   name);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showAlert(panelAlertId, data.message || 'An error occurred.', 'error');
                        return;
                    }

                    /* Remove empty-state row if present */
                    const emptyRow = document.getElementById(emptyId);
                    if (emptyRow) emptyRow.remove();

                    const tbody    = document.getElementById(tbodyId);
                    const rowCount = tbody.querySelectorAll('tr').length + 1;

                    const tr = document.createElement('tr');
                    tr.setAttribute('data-id', data.id);
                    tr.innerHTML =
                        '<td>' + rowCount + '</td>' +
                        '<td>' + escapeHtml(data.name) + '</td>' +
                        '<td>' +
                            '<button class="gs-btn-delete"' +
                            '        onclick="deleteRecord(\'' + type + '\', ' + data.id + ', this)"' +
                            '        title="Delete">' +
                            '    <img src="assets/icons/delete_icon.png" alt="Delete">' +
                            '</button>' +
                        '</td>';
                    tbody.appendChild(tr);

                    input.value = '';
                    /* Clear error state after successful save */
                    const errEl = document.getElementById(errorElId);
                    if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                    input.classList.remove('gs-input--error');

                    showAlert(panelAlertId, '"' + data.name + '" added successfully!', 'success');
                })
                .catch(function () {
                    showAlert(panelAlertId, 'Network error. Please try again.', 'error');
                });
        }
    );
}


/* ══════════════════════════════════════════════
   6. DELETE
   ══════════════════════════════════════════════ */
function deleteRecord(type, id, btn) {
    const action       = type === 'designation' ? 'delete_designation' : 'delete_funding';
    const tbodyId      = type === 'designation' ? 'designation-body'   : 'funding-body';
    const emptyId      = type === 'designation' ? 'designation-empty'  : 'funding-empty';
    const panelAlertId = type === 'designation' ? 'designation-alert'  : 'funding-alert';

    const row      = btn.closest('tr');
    const rowName  = row.cells[1].textContent.trim();

    showConfirm(
        'Are you sure you want to delete <strong>"' + escapeHtml(rowName) + '"</strong>?<br>' +
        '<span style="font-size:0.82rem;color:#c0392b;">This action cannot be undone.</span>',
        function () {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id',     id);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showAlert(panelAlertId, data.message || 'Delete failed.', 'error');
                        return;
                    }

                    row.remove();
                    renumberRows(tbodyId);

                    const tbody    = document.getElementById(tbodyId);
                    const rowsLeft = tbody.querySelectorAll('tr').length;
                    if (rowsLeft === 0) {
                        const empty     = document.createElement('tr');
                        empty.className = 'gs-empty-row';
                        empty.id        = emptyId;
                        empty.innerHTML = '<td colspan="3">No records yet.</td>';
                        tbody.appendChild(empty);
                    }

                    showAlert(panelAlertId, '"' + rowName + '" has been deleted.', 'success');
                })
                .catch(function () {
                    showAlert(panelAlertId, 'Network error. Please try again.', 'error');
                });
        }
    );
}


/* ══════════════════════════════════════════════
   7. INIT
   ══════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
    attachInputValidation('designation-input', 'designation-input-error');
    attachInputValidation('funding-input',     'funding-input-error');

    /* Enter key triggers save */
    document.getElementById('designation-input')
        .addEventListener('keydown', function (e) {
            if (e.key === 'Enter') saveRecord('designation');
        });
    document.getElementById('funding-input')
        .addEventListener('keydown', function (e) {
            if (e.key === 'Enter') saveRecord('funding');
        });
});