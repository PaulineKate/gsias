(function () {

    const createForm       = document.getElementById('createForm');
    const sendCodeBtn      = document.getElementById('sendCodeBtn');
    const gmailInput       = document.getElementById('gmail_account');
    const fullNameInput    = document.getElementById('full_name');
    const verifiedBadge    = document.getElementById('verifiedBadge');
    const verifiedEmailFld = document.getElementById('verifiedEmail');

    const modal         = document.getElementById('verifyModal');
    const modalEmail    = document.getElementById('modalEmailDisplay');
    const codeInputs    = document.querySelectorAll('#codeInputs input');
    const codeError     = document.getElementById('codeError');
    const confirmBtn    = document.getElementById('modalConfirmBtn');
    const cancelBtn     = document.getElementById('modalCancelBtn');
    const resendLink    = document.getElementById('resendLink');
    const countdownEl   = document.getElementById('countdownDisplay');

    const passInput         = document.getElementById('password');
    const confirmPassInput  = document.getElementById('confirm_password');
    const togglePassBtn     = document.getElementById('togglePass');
    const toggleConfirmBtn  = document.getElementById('toggleConfirmPass');
    const eyePassIcon       = document.getElementById('eyePassIcon');
    const eyeConfirmIcon    = document.getElementById('eyeConfirmIcon');

    let countdownTimer = null;
    let currentEmail   = '';

    const profileImageInput = document.getElementById('profile_image');
    const avatarPreview     = document.getElementById('avatarPreview');

    if (profileImageInput && avatarPreview) {
        profileImageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { avatarPreview.src = e.target.result; };
                reader.readAsDataURL(file);
            }
        });
    }

    function setupToggle(btn, input, icon) {
        if (!btn || !input || !icon) return;
        btn.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.src   = isHidden ? 'assets/icons/password_visible_icon.png' : 'assets/icons/password_invisible_icon.png';
            icon.alt   = isHidden ? 'Hide password'      : 'Show password';
        });
    }

    setupToggle(togglePassBtn,    passInput,        eyePassIcon);
    setupToggle(toggleConfirmBtn, confirmPassInput, eyeConfirmIcon);

    if (gmailInput) {
        gmailInput.addEventListener('input', () => {
            verifiedBadge.classList.remove('show');
            verifiedEmailFld.value  = '';
            sendCodeBtn.disabled    = false;
            sendCodeBtn.textContent = 'VERIFY';
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            const pass    = passInput ? passInput.value : '';
            const confirm = confirmPassInput ? confirmPassInput.value : '';

            if (pass.length < 8) {
                e.preventDefault();
                showFormError('Password must be at least 8 characters long.');
                passInput.focus();
                return;
            }

            if (pass !== confirm) {
                e.preventDefault();
                showFormError('Passwords do not match.');
                confirmPassInput.focus();
                return;
            }
        });
    }

    /* Helper: show an inline error above the submit button */
    function showFormError(msg) {
        let el = document.getElementById('jsFormError');
        if (!el) {
            el = document.createElement('div');
            el.id = 'jsFormError';
            el.className = 'ac-alert ac-alert--error';
            const btnRow = document.querySelector('.ac-btn-row');
            if (btnRow) btnRow.before(el);
        }
        el.textContent = msg;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 5000);
    }

    if (sendCodeBtn) {
        sendCodeBtn.addEventListener('click', () => {
            const gmail = gmailInput.value.trim();
            const name  = fullNameInput.value.trim() || 'User';

            if (!gmail) {
                alert('Please enter an email address first.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(gmail)) {
                alert('Please enter a valid email address.');
                return;
            }

            sendCodeBtn.disabled    = true;
            sendCodeBtn.textContent = '...';

            const fd = new FormData();
            fd.append('action', 'send_code');
            fd.append('gmail',  gmail);
            fd.append('name',   name);

            fetch('account_creation.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        currentEmail = gmail;
                        openModal(gmail);
                    } else {
                        alert(data.message);
                        sendCodeBtn.disabled    = false;
                        sendCodeBtn.textContent = 'VERIFY';
                    }
                })
                .catch(() => {
                    alert('Network error. Please try again.');
                    sendCodeBtn.disabled    = false;
                    sendCodeBtn.textContent = 'VERIFY';
                });
        });
    }

    function openModal(email) {
        codeInputs.forEach(i => { i.value = ''; i.classList.remove('error'); });
        codeError.textContent  = '';
        confirmBtn.disabled    = false;
        confirmBtn.textContent = 'CONFIRM';
        modalEmail.textContent = email;
        modal.classList.add('show');
        codeInputs[0].focus();
        startCountdown(600);
    }

    codeInputs.forEach((input, idx) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '');
            if (input.value && idx < codeInputs.length - 1) {
                codeInputs[idx + 1].focus();
            }
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) {
                codeInputs[idx - 1].focus();
            }
        });

        input.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '');
            [...pasted].slice(0, 6).forEach((char, i) => {
                if (codeInputs[i]) codeInputs[i].value = char;
            });
            const lastFilled = Math.min(pasted.length, 5);
            codeInputs[lastFilled].focus();
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const code = [...codeInputs].map(i => i.value).join('');

            if (code.length < 6) {
                codeError.textContent = 'Please enter all 6 digits.';
                codeInputs.forEach(i => i.classList.add('error'));
                return;
            }

            confirmBtn.disabled    = true;
            confirmBtn.textContent = 'VERIFYING…';

            const fd = new FormData();
            fd.append('action', 'verify_code');
            fd.append('code',   code);
            fd.append('gmail',  currentEmail);

            fetch('account_creation.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        clearInterval(countdownTimer);
                        modal.classList.remove('show');
                        verifiedBadge.classList.add('show');
                        verifiedEmailFld.value  = currentEmail;
                        sendCodeBtn.disabled    = true;
                        sendCodeBtn.textContent = '✓';
                    } else {
                        codeError.textContent = data.message;
                        codeInputs.forEach(i => i.classList.add('error'));
                        confirmBtn.disabled    = false;
                        confirmBtn.textContent = 'CONFIRM';
                    }
                })
                .catch(() => {
                    codeError.textContent  = 'Network error. Please try again.';
                    confirmBtn.disabled    = false;
                    confirmBtn.textContent = 'CONFIRM';
                });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            clearInterval(countdownTimer);
            modal.classList.remove('show');
            sendCodeBtn.disabled    = false;
            sendCodeBtn.textContent = 'VERIFY';
        });
    }

    if (resendLink) {
        resendLink.addEventListener('click', () => {
            clearInterval(countdownTimer);
            codeInputs.forEach(i => { i.value = ''; i.classList.remove('error'); });
            codeError.textContent   = '';
            countdownEl.textContent = '';
            confirmBtn.disabled     = false;
            confirmBtn.textContent  = 'CONFIRM';

            const fd = new FormData();
            fd.append('action', 'send_code');
            fd.append('gmail',  currentEmail);
            fd.append('name',   fullNameInput.value.trim() || 'User');

            resendLink.textContent = 'Sending…';

            fetch('account_creation.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    resendLink.textContent = "Didn't receive it? Resend code";
                    if (data.success) {
                        codeError.textContent = '';
                        startCountdown(600);
                        codeInputs[0].focus();
                    } else {
                        codeError.textContent = data.message;
                    }
                })
                .catch(() => {
                    resendLink.textContent = "Didn't receive it? Resend code";
                    codeError.textContent  = 'Network error. Please try again.';
                });
        });
    }

    function startCountdown(seconds) {
        let remaining = seconds;
        countdownEl.textContent = 'Code expires in ' + formatTime(remaining);

        countdownTimer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                countdownEl.textContent = 'Code expired. Please request a new one.';
                confirmBtn.disabled     = true;
            } else {
                countdownEl.textContent = 'Code expires in ' + formatTime(remaining);
            }
        }, 1000);
    }

    function formatTime(s) {
        const m   = Math.floor(s / 60);
        const sec = s % 60;
        return m + ':' + String(sec).padStart(2, '0');
    }

    const successFlag = document.getElementById('acSuccessFlag');
    if (successFlag) {
        // Inject a visible success alert
        const alert = document.createElement('div');
        alert.className  = 'ac-alert ac-alert--success';
        alert.textContent = 'Account created successfully.';
        successFlag.replaceWith(alert);

        // Redirect to dashboard after 2 seconds
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);
    }

})();