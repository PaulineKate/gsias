(function () {
    if (document.getElementById('asPopupStyle')) return;
    const s = document.createElement('style');
    s.id = 'asPopupStyle';
    s.textContent =
        '@keyframes asFadeIn  { from { opacity:0; transform:scale(0.96); }' +
                               ' to   { opacity:1; transform:scale(1);    } }' +
        '@keyframes asFadeOut { from { opacity:1; } to { opacity:0; } }';
    document.head.appendChild(s);
})();

function showConfirm(message, onConfirm) {
    const existing = document.getElementById('asConfirmOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'asConfirmOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:420px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;' +
        'animation:asFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2rem;margin-bottom:10px;">💾</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:24px;">' +
            message +
        '</p>' +
        '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button id="asCancelBtn"' +
            ' style="padding:9px 26px;background:#e8f0e8;color:#1a3d1f;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Cancel</button>' +
            '<button id="asConfirmBtn"' +
            ' style="padding:9px 26px;background:#2a5c30;color:#fff;border:none;' +
            'border-radius:8px;font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;' +
            'font-weight:700;text-transform:uppercase;cursor:pointer;">Confirm</button>' +
        '</div>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
    document.getElementById('asCancelBtn').addEventListener('click', function () {
        overlay.remove();
    });
    document.getElementById('asConfirmBtn').addEventListener('click', function () {
        overlay.remove();
        onConfirm();
    });
}

function showSuccess(message) {
    const existing = document.getElementById('asSuccessOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'asSuccessOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:400px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;' +
        'animation:asFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2.2rem;margin-bottom:10px;">✅</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:22px;">' +
            message +
        '</p>' +
        '<button id="asOkBtn"' +
        ' style="padding:9px 30px;background:#2a5c30;color:#fff;border:none;border-radius:8px;' +
        'font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;font-weight:700;' +
        'text-transform:uppercase;cursor:pointer;letter-spacing:0.3px;">OK</button>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById('asOkBtn').addEventListener('click', function () {
        overlay.remove();
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
}

function showPopup(message, type) {
    const existing = document.getElementById('asInfoOverlay');
    if (existing) existing.remove();

    const icons = { warning: '⚠️', info: 'ℹ️', success: '✅' };
    const icon  = icons[type] || 'ℹ️';

    const overlay = document.createElement('div');
    overlay.id = 'asInfoOverlay';
    overlay.style.cssText =
        'position:fixed;inset:0;background:rgba(0,0,0,0.38);z-index:9999;' +
        'display:flex;align-items:center;justify-content:center;';

    const box = document.createElement('div');
    box.style.cssText =
        'background:#fff;border-radius:14px;padding:32px 36px;max-width:400px;width:90%;' +
        'box-shadow:0 8px 32px rgba(26,61,31,0.2);text-align:center;' +
        'font-family:\'Source Sans 3\',sans-serif;' +
        'animation:asFadeIn 0.2s ease;';

    box.innerHTML =
        '<div style="font-size:2.2rem;margin-bottom:10px;">' + icon + '</div>' +
        '<p style="color:#1a2e1c;font-size:0.92rem;line-height:1.65;margin-bottom:22px;">' +
            message +
        '</p>' +
        '<button id="asInfoOkBtn"' +
        ' style="padding:9px 30px;background:#2a5c30;color:#fff;border:none;border-radius:8px;' +
        'font-family:\'Source Sans 3\',sans-serif;font-size:0.88rem;font-weight:700;' +
        'text-transform:uppercase;cursor:pointer;letter-spacing:0.3px;">OK</button>';

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById('asInfoOkBtn').addEventListener('click', function () {
        overlay.remove();
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.remove();
    });
}

(function () {

    const fileInput     = document.getElementById('profile_image');
    const avatarPreview = document.getElementById('avatarPreview');

    if (fileInput && avatarPreview) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showPopup('Please select a valid image file (JPG, PNG, GIF, WEBP).', 'warning');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    const form         = document.getElementById('accountForm');
    const passInput    = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const nameInput    = document.getElementById('full_name');
    const userInput    = document.getElementById('username');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const name    = nameInput    ? nameInput.value.trim()    : '';
        const user    = userInput    ? userInput.value.trim()    : '';
        const pass    = passInput    ? passInput.value.trim()    : '';
        const confirm = confirmInput ? confirmInput.value.trim() : '';

        if (!name || !user) {
            showPopup('Full Name and Username cannot be empty.', 'warning');
            return;
        }

        if (pass !== '' && pass !== confirm) {
            showPopup('Passwords do not match. Please try again.', 'warning');
            confirmInput.focus();
            return;
        }

        const changingPassword = pass !== '';

        showConfirm(
            'You are about to update your account details.<br><br>' +
            '<span style="font-size:0.85rem;color:#2a5c30;">' +
                '👤 Name: <strong>' + name + '</strong><br>' +
                '🔑 Username: <strong>' + user + '</strong><br>' +
                '🔒 Password: <strong>' + (changingPassword ? 'Will be changed' : 'Unchanged') + '</strong>' +
            '</span><br><br>' +
            'Proceed?',
            function () { form.submit(); }
        );
    });

    /* ── PHP flag checks on page load ── */
    const successFlag  = document.getElementById('asSuccessFlag');
    const noChangeFlag = document.getElementById('asNoChangeFlag');

    if (successFlag) {
        showSuccess('Your account details have been updated successfully!');
    }

    if (noChangeFlag) {
        showPopup('No changes were made to your account details.', 'info');
    }

})();