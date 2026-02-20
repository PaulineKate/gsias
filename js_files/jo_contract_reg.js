/* ── Inject fade-in keyframe once ── */
(function () {
    if (document.getElementById('joregPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'joregPopupStyle';
    s.textContent =
        '@keyframes joFadeIn { from { opacity:0; transform:scale(0.96); }' +
                             '  to   { opacity:1; transform:scale(1);    } }';
    document.head.appendChild(s);
})();


/* 1. CONFIRM POPUP UTILITY */

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
        'font-family:\'Source Sans 3\',sans-serif;' +
        'animation:joFadeIn 0.2s ease;';

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

    /* Close on backdrop click */
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


/* 2. INLINE ALERT HELPER  (banner inside the page, not a modal) */
function showAlert(msg, type) {
    const el = document.getElementById('joregAlert');
    if (!el) return;
    el.className   = 'joreg-alert ' + type;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    setTimeout(function () { el.className = 'joreg-alert'; }, 5000);
}


/* 3. MAIN PAGE LOGIC */

(function () {

    const rowsContainer  = document.getElementById('joregRows');
    const pdfInput       = document.getElementById('joregPdfFile');
    const pdfNameEl      = document.getElementById('joregPdfName');
    const refFolderInput = document.getElementById('joregRefFolder');
    let   rowCount       = 1;

    /* ── PDF selected → auto-fill Reference Folder ── */
    if (pdfInput) {
        pdfInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const baseName = fileName.replace(/\.[^/.]+$/, '');
                pdfNameEl.textContent = fileName;
                /* Only auto-fill if the field is still empty */
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
        rowCount++;
        const row = document.createElement('div');
        row.className    = 'joreg-entry-row';
        row.dataset.rowId = rowCount;
        row.innerHTML =
            '<input type="text" class="joreg-input" name="name[]"            placeholder="Full name"       required>' +
            '<input type="text" class="joreg-input" name="designation[]"     placeholder="Designation"     required>' +
            '<input type="text" class="joreg-input" name="funding_charges[]" placeholder="Funding charges" required>' +
            '<input type="text" class="joreg-input" name="rate[]"            placeholder="0.00"            required>' +
            '<button type="button" class="joreg-btn-remove-row" onclick="removeRow(this)" title="Remove row">×</button>';
        rowsContainer.appendChild(row);
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

    /* ── Form submit: validate → confirm popup → submit ── */
    const form = document.getElementById('joregForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const dateFrom  = document.getElementById('joregDateFrom').value.trim();
        const dateTo    = document.getElementById('joregDateTo').value.trim();
        const refFolder = refFolderInput ? refFolderInput.value.trim() : '';

        /* Validation */
        if (!dateFrom || !dateTo) {
            showAlert('Please fill in both FROM and TO dates.', 'error');
            return;
        }
        if (!refFolder) {
            showAlert('Please enter a Reference Folder name or upload a PDF.', 'error');
            return;
        }

        let allFilled = true;
        rowsContainer.querySelectorAll('.joreg-entry-row').forEach(function (row) {
            row.querySelectorAll('input').forEach(function (inp) {
                if (inp.value.trim() === '') allFilled = false;
            });
        });
        if (!allFilled) {
            showAlert('Please fill in all Name, Designation, Funding Charges, and Rate fields.', 'error');
            return;
        }

        /* Build confirm message */
        const totalRows = rowsContainer.querySelectorAll('.joreg-entry-row').length;
        const hasPdf    = pdfInput && pdfInput.files && pdfInput.files.length > 0;

        showConfirm(
            'You are about to save <strong>' + totalRows + '</strong> record(s).<br>' +
            '<span style="font-size:0.82rem;color:#7a9e7e;">' +
                'PDF: ' + (hasPdf ? '✅ Attached' : '❌ None — will show as <em>Unavailable</em>') +
            '</span><br><br>Proceed?',
            function () { form.submit(); }
        );
    });

})();