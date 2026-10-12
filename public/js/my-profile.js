document.querySelectorAll('[data-password-target]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + document.querySelector(`label[for="${input.id}"]`).textContent.replace('*','').trim().toLowerCase());
    });
});
const passwordForm = document.getElementById('changePasswordForm');
if (passwordForm) {
    passwordForm.noValidate = true;
    const current = passwordForm.elements.current_password;
    const password = passwordForm.elements.password;
    const confirmation = passwordForm.elements.password_confirmation;
    function validate(input) {
        let message = '';
        if (!input.value) message = 'This field is required.';
        else if (input === password && [...password.value].length < 12) message = 'Use at least 12 characters.';
        else if (input === password && !password.value.trim()) message = 'Choose a password containing more than spaces.';
        else if (input === password && password.value === current.value) message = 'Choose a password different from your current password.';
        else if (input === confirmation && confirmation.value !== password.value) message = 'The new password and confirmation do not match.';
        document.getElementById(input.id + 'Error').textContent = message;
        input.setAttribute('aria-invalid', String(Boolean(message)));
        return !message;
    }
    [current,password,confirmation].forEach(input => {
        input.addEventListener('blur', () => validate(input));
        input.addEventListener('input', () => {
            if (input.getAttribute('aria-invalid') === 'true') validate(input);
            if (input === password && confirmation.value) validate(confirmation);
        });
    });
    passwordForm.addEventListener('submit', event => {
        const invalid = [current,password,confirmation].filter(input => !validate(input));
        if (invalid.length) { event.preventDefault(); invalid[0].focus(); }
    });
}
document.querySelectorAll('[data-profile-form],#changePasswordForm').forEach(form => {
    const button = form.querySelector('[type="submit"]');
    const originalText = button.textContent;
    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.textContent = originalText;
        form.removeAttribute('aria-busy');
    });
    form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        if (button.disabled) { event.preventDefault(); return; }
        button.disabled = true;
        button.textContent = 'Updating…';
        form.setAttribute('aria-busy','true');
    });
});
const success = document.querySelector('[data-profile-success]');
if (success) setTimeout(() => success.remove(), 3000);
