(function () {

    function setupToggle(btnId, inputId, iconId) {
        const btn   = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (!btn || !input || !icon) return;

        btn.addEventListener('click', function () {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.src   = isHidden ? 'assets/icons/password_visible_icon.png'
                                  : 'assets/icons/password_invisible_icon.png';
            icon.alt   = isHidden ? 'Hide password' : 'Show password';
        });
    }

    setupToggle('toggleNewPass',     'new_password',     'eyeNewPassIcon');
    setupToggle('toggleConfirmPass', 'confirm_password', 'eyeConfirmIcon');

})();