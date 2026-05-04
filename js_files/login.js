(function () {

    const passwordInput = document.getElementById('password');
    const toggleBtn     = document.getElementById('togglePass');
    const eyeIcon       = document.getElementById('eyePassIcon');

    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIcon.src        = isHidden ? 'assets/icons/password_visible_icon.png'
                                          : 'assets/icons/password_invisible_icon.png';
            eyeIcon.alt        = isHidden ? 'Hide password' : 'Show password';
        });
    }

})();