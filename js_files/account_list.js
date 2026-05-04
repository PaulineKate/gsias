(function () {

    const searchInput   = document.getElementById('acctSearch');
    const tableBody     = document.getElementById('acctTableBody');
    const backdrop      = document.getElementById('deleteModalBackdrop');
    const modal         = document.getElementById('deleteModal');
    const targetName    = document.getElementById('deleteTargetName');
    const deleteUserInput = document.getElementById('deleteUserInput');
    const pwInput       = document.getElementById('confirm_password');
    const toggleBtn     = document.getElementById('toggleModalPass');
    const eyeIcon       = document.getElementById('eyeModalIcon');
    const liveError     = document.getElementById('modalErrorLive');
    const staticError   = document.getElementById('modalErrorStatic');

    const dataRows = tableBody
        ? Array.from(tableBody.querySelectorAll('tr.acct-data-row'))
        : [];

    /* ── Stripe helper ───────────────────────────────────────────────────── */
    function restripe() {
        let idx = 0;
        dataRows.forEach(function (row) {
            if (row.style.display === 'none') return;
            row.classList.remove('row-odd', 'row-even');
            row.classList.add(idx % 2 === 0 ? 'row-odd' : 'row-even');
            idx++;
        });
    }

    restripe();

    if (searchInput && dataRows.length) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visible = 0;

            dataRows.forEach(function (row) {
                const name  = (row.dataset.name  || '').toLowerCase();
                const gmail = (row.dataset.gmail || '').toLowerCase();
                const level = (row.dataset.level || '').toLowerCase();

                const matches = !query
                    || name.startsWith(query)
                    || gmail.includes(query)
                    || level.includes(query);

                row.style.display = matches ? '' : 'none';
                if (matches) visible++;
            });

            restripe();

            let noRow = tableBody.querySelector('tr.no-results-live');
            if (visible === 0 && query) {
                if (!noRow) {
                    noRow = document.createElement('tr');
                    noRow.className = 'no-results-live';
                    noRow.innerHTML =
                        '<td colspan="5" style="text-align:center;padding:28px;' +
                        'color:#7a9e7a;font-style:italic;">' +
                        'No accounts match &ldquo;' + escHtml(query) + '&rdquo;.</td>';
                    tableBody.insertBefore(noRow, tableBody.firstChild);
                } else {
                    noRow.style.display = '';
                    noRow.querySelector('td').innerHTML =
                        'No accounts match &ldquo;' + escHtml(query) + '&rdquo;.';
                }
            } else if (noRow) {
                noRow.style.display = 'none';
            }
        });
    }

    window.openDeleteModal = function (btn) {
        const user = btn.dataset.user;   // admin_user value
        const name = btn.dataset.name;

        deleteUserInput.value  = user;
        targetName.textContent = name;

        if (pwInput)   pwInput.value = '';
        if (liveError) { liveError.style.display = 'none'; liveError.textContent = ''; }

        backdrop.classList.add('active');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        setTimeout(function () { if (pwInput) pwInput.focus(); }, 120);
    };

    window.closeDeleteModal = function () {
        backdrop.classList.remove('active');
        modal.classList.remove('active');
        document.body.style.overflow = '';
        if (pwInput) pwInput.value = '';
    };

    /* Close on backdrop click */
    if (backdrop) {
        backdrop.addEventListener('click', window.closeDeleteModal);
    }

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            window.closeDeleteModal();
        }
    });

    if (toggleBtn && pwInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            eyeIcon.src  = isHidden
                ? 'assets/icons/password_visible_icon.png'
                : 'assets/icons/password_invisible_icon.png';
            eyeIcon.alt  = isHidden ? 'Hide password' : 'Show password';
        });
    }

    if (staticError) {
        if (liveError) {
            liveError.textContent  = staticError.textContent.trim();
            liveError.style.display = '';
            staticError.style.display = 'none';
        }
        const storedUser = deleteUserInput ? deleteUserInput.value : '';
        if (storedUser) {
            backdrop.classList.add('active');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})();