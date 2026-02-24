
(function () {
    if (document.getElementById('gsPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'gsPopupStyle';
    s.textContent =
        '@keyframes gsFadeIn { from { opacity:0; transform:scale(0.96); }' +
                             '  to   { opacity:1; transform:scale(1);    } }';
    document.head.appendChild(s);
})();

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

function showConfirmDanger(message, onConfirm) {
    const existing = document.getElementById('gsConfirmOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'gsConfirmOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:440px;width:90%;' +
        'box-shadow:0 8px 40px rgba(192,57,43,0.25);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;animation:gsFadeIn 0.2s ease;' +
        'border-top:4px solid #c0392b;';

    box.innerHTML =
        '<div style="font-size:2.2rem;margin-bottom:10px;">🗑️</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.7;margin-bottom:8px;">' +
            message +
        '</p>' +
        '<p style="background:#fff3f3;border:1px solid #f0b8b8;border-radius:8px;' +
            'padding:10px 14px;font-size:0.82rem;color:#c0392b;font-weight:600;' +
            'margin-bottom:24px;line-height:1.5;">' +
            '⚠️ Deleting this folder will remove all registered personnel related to it.' +
        '</p>' +
        '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button id="gsCancelBtn"' +
            ' style="padding:9px 26px;background:#e8f0e8;color:#1a3d1f;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Cancel</button>' +
            '<button id="gsConfirmBtn"' +
            ' style="padding:9px 26px;background:#c0392b;color:#fff;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Delete Folder</button>' +
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

function deleteRefFolder(refFolder, btn) {
    const row     = btn.closest('tr');
    const rowName = row.cells[1].textContent.trim();

    showConfirmDanger(
        'You are about to delete the folder <strong>"' + escapeHtml(rowName) + '"</strong>.',
        function () {
            const fd = new FormData();
            fd.append('action',     'delete_ref_folder');
            fd.append('ref_folder', refFolder);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showAlert('ref-folder-alert', data.message || 'Delete failed.', 'error');
                        return;
                    }

                    row.remove();
                    renumberVisibleRows('ref-folder-body');

                    const tbody    = document.getElementById('ref-folder-body');
                    const rowsLeft = tbody.querySelectorAll('tr:not(.gs-empty-row)').length;
                    if (rowsLeft === 0) {
                        const empty     = document.createElement('tr');
                        empty.className = 'gs-empty-row';
                        empty.id        = 'ref-folder-empty';
                        empty.innerHTML = '<td colspan="3">No ref. folders found.</td>';
                        tbody.appendChild(empty);
                    }

                    showAlert('ref-folder-alert', '"' + rowName + '" folder and all related records have been deleted.', 'success');
                })
                .catch(function () {
                    showAlert('ref-folder-alert', 'Network error. Please try again.', 'error');
                });
        }
    );
}

function renumberVisibleRows(tbodyId) {
    const rows = document.querySelectorAll('#' + tbodyId + ' tr:not(.gs-empty-row)');
    let counter = 1;
    rows.forEach(function (row) {
        if (row.style.display !== 'none') {
            row.cells[0].textContent = counter++;
        }
    });
}

function attachRefFolderSearch() {
    const searchInput = document.getElementById('ref-folder-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query  = this.value.trim().toLowerCase();
        const tbody  = document.getElementById('ref-folder-body');
        const rows   = tbody.querySelectorAll('tr:not(.gs-empty-row)');
        let   visible = 0;

        rows.forEach(function (row) {
            const folderName = row.cells[1].textContent.toLowerCase();
            if (query === '' || folderName.includes(query)) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        let noResultsRow = document.getElementById('ref-folder-no-results');
        if (visible === 0 && rows.length > 0) {
            if (!noResultsRow) {
                noResultsRow     = document.createElement('tr');
                noResultsRow.id  = 'ref-folder-no-results';
                noResultsRow.className = 'gs-empty-row';
                noResultsRow.innerHTML = '<td colspan="3">No folders match your search.</td>';
                tbody.appendChild(noResultsRow);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }

        renumberVisibleRows('ref-folder-body');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    attachInputValidation('designation-input', 'designation-input-error');
    attachInputValidation('funding-input',     'funding-input-error');

    document.getElementById('designation-input')
        .addEventListener('keydown', function (e) {
            if (e.key === 'Enter') saveRecord('designation');
        });
    document.getElementById('funding-input')
        .addEventListener('keydown', function (e) {
            if (e.key === 'Enter') saveRecord('funding');
        });

    attachRefFolderSearch();
});

function runManualBackup() {
    const destDir = document.getElementById('manualBackupDir').value.trim();
    if (!destDir) {
        showAlert('manual-backup-alert', 'Please enter a destination directory.', 'error');
        document.getElementById('manualBackupDir').focus();
        return;
    }

    showConfirm(
        'Create a full backup now?<br>' +
        '<span style="font-size:0.82rem;color:#4a7a50;">Destination: <strong>' + escapeHtml(destDir) + '</strong></span><br>' +
        '<span style="font-size:0.82rem;color:#7a9e7e;">This may take a moment depending on file size.</span>',
        function () {
            const btn = document.getElementById('manualBackupBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation:gsSpin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>' +
                    ' Creating Backup…';
            }

            if (!document.getElementById('gsSpinStyle')) {
                const s = document.createElement('style');
                s.id = 'gsSpinStyle';
                s.textContent = '@keyframes gsSpin { to { transform: rotate(360deg); } }';
                document.head.appendChild(s);
            }

            const fd = new FormData();
            fd.append('action',   'manual_backup');
            fd.append('dest_dir', destDir);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
                            ' Create Backup Now';
                    }

                    if (!data.success) {
                        showAlert('manual-backup-alert', '❌ ' + (data.message || 'Backup failed.'), 'error');
                        return;
                    }

                    let msg = '✅ Backup created: <strong>' + escapeHtml(data.zip_name) + '</strong> (' + data.size_kb + ' KB)';
                    if (!data.sql_ok) {
                        msg += '<br><span style="color:#c0392b;font-size:0.78rem;">⚠️ Database dump failed — check mysqldump. PDFs were still backed up.</span>';
                    }

                    showBackupSuccess(data);
                })
                .catch(function () {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
                            ' Create Backup Now';
                    }
                    showAlert('manual-backup-alert', 'Network error. Please try again.', 'error');
                });
        }
    );
}

function showBackupSuccess(data) {
    const existing = document.getElementById('gsBackupSuccessOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'gsBackupSuccessOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const sqlStatus = data.sql_ok
        ? '<span style="color:#2a5c30;">✅ Database dump</span>'
        : '<span style="color:#c0392b;">⚠️ Database dump failed</span>';

    overlay.innerHTML =
        '<div style="background:#fff;border-radius:14px;padding:32px 36px;max-width:440px;width:92%;' +
            'box-shadow:0 8px 40px rgba(26,61,31,0.22);text-align:center;' +
            'font-family:\'Source Sans 3\',sans-serif;animation:gsFadeIn 0.2s ease;' +
            'border-top:4px solid #2a5c30;">' +
            '<div style="font-size:2.4rem;margin-bottom:8px;">📦</div>' +
            '<div style="font-family:\'Barlow\',sans-serif;font-size:15px;font-weight:800;' +
                'text-transform:uppercase;color:#1a3d1f;letter-spacing:0.4px;margin-bottom:16px;">Backup Complete</div>' +
            '<div style="background:#f4fbf4;border:1px solid #c8e6c9;border-radius:10px;padding:14px 18px;' +
                'text-align:left;font-size:13px;line-height:2;margin-bottom:20px;">' +
                '<div><strong>File:</strong> ' + escapeHtml(data.zip_name) + '</div>' +
                '<div><strong>Size:</strong> ' + data.size_kb + ' KB</div>' +
                '<div><strong>PDFs backed up:</strong> ' + data.pdf_count + ' file(s)</div>' +
                '<div><strong>Database:</strong> ' + sqlStatus + '</div>' +
                (data.dump_error ? '<div style="font-size:0.75rem;color:#c0392b;margin-top:4px;">Error: ' + escapeHtml(data.dump_error.substring(0, 120)) + '</div>' : '') +
            '</div>' +
            '<button onclick="document.getElementById(\'gsBackupSuccessOverlay\').remove()"' +
                ' style="padding:10px 30px;background:#1a3d1f;color:#fff;border:none;border-radius:8px;' +
                'font-family:\'Barlow\',sans-serif;font-size:13px;font-weight:700;text-transform:uppercase;cursor:pointer;">Done</button>' +
        '</div>';

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
    document.body.appendChild(overlay);
}