<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="robots" content="noindex, nofollow"/>
    <meta name="referrer" content="no-referrer"/>
    <title>Login – Barangay Doyong EDRMS</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin:0; min-height:100vh; min-height:100svh; display:flex; align-items:center; justify-content:center; padding:32px 18px; background:#FAFAF7; color:#172B22; font-family:Arial, 'Segoe UI', sans-serif; }
        body {
            background-image: linear-gradient(rgba(250,250,247,.68), rgba(250,250,247,.72)), url('{{ asset('images/barangay-hall-background.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .login-wrapper { width:100%; max-width:420px; }
        .login-header { text-align:center; margin-bottom:28px; }
        .login-logo { display:block; width:105px; height:105px; object-fit:contain; margin:0 auto 18px; }
        .login-title { margin:0 0 9px; color:#166534; font-family:Georgia, 'Times New Roman', serif; font-weight:700; font-size:27px; letter-spacing:.3px; }
        .login-subtitle { margin:0; color:#68756D; font-size:13px; line-height:1.6; }
        .login-card { padding:28px 30px; background:#FFFFFF; border:1px solid #E1E9E1; border-top:3px solid #DCFCE7; border-radius:16px; box-shadow:0 10px 35px #172B220A,0 2px 8px #172B2205; }
        .card-title { font:700 24px/1.2 Georgia, 'Times New Roman', serif; margin:0 0 24px; color:#166534; }
        .form-group { margin-bottom:18px; }
        .form-label { display:block; margin-bottom:8px; font-size:13px; font-weight:600; }
        .input-wrapper { position:relative; }
        .form-input { display:block; width:100%; height:48px; border:1px solid #D5DED6; border-radius:9px; padding:0 13px; background:#FFF; color:#172B22; font:14px Arial, sans-serif; outline:none; }
        .form-input::placeholder { color:#89958D; }
        .form-input:focus { border-color:#15803D; box-shadow:0 0 0 3px #DCFCE7; }
        .form-input[aria-invalid="true"] { border-color:#B91C1C; }
        #password { padding-right:65px; }
        .toggle-password { position:absolute; right:7px; top:8px; height:32px; border:0; border-radius:6px; padding:0 9px; color:#166534; background:#F0FDF4; cursor:pointer; font-weight:600; }
        .toggle-password { display:flex; align-items:center; justify-content:center; width:36px; padding:0; }
        .toggle-password svg { width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .toggle-password .eye-slash { display:none; }
        .toggle-password[aria-pressed="true"] .eye-slash { display:block; }
        :is(button,a,input):focus-visible { outline:2px solid #15803D; outline-offset:3px; }
        .field-error-text { display:block; color:#B91C1C; font-size:12px; line-height:1.5; margin-top:6px; }
        .login-message { padding:10px 12px; border-radius:8px; font-size:13px; line-height:1.5; margin-bottom:18px; background:#F0FDF4; color:#166534; }
        .login-error { background:#FEF2F2; color:#B91C1C; }
        .remember-username { display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; margin:0 0 20px; }
        .remember-username input { width:16px; height:16px; margin:0; accent-color:#15803D; }
        .btn-login { display:flex; justify-content:center; align-items:center; gap:9px; width:100%; height:48px; border:0; border-radius:9px; background:#15803D; color:white; font-size:15px; font-weight:700; cursor:pointer; transition:background .15s; }
        .btn-login:hover { background:#166534; }
        .btn-login:disabled { opacity:.75; cursor:wait; }
        .btn-spinner { display:none; align-items:center; gap:9px; }
        .spinner-circle { width:17px; height:17px; border:2px solid #ffffff55; border-top-color:white; border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .is-loading .btn-content { display:none; }
        .is-loading .btn-spinner { display:flex; }
        .form-options-row { margin-top:18px; text-align:right; }
        .forgot-password { color:#166534; font-size:13px; text-decoration:none; }
        .forgot-password:hover { text-decoration:underline; }
        .login-footer { text-align:center; margin-top:22px; color:#7A867F; font-size:12px; line-height:1.5; }
        @media(max-width:480px) { body { padding:24px 16px; } .login-card { padding:24px 22px; } .login-title { font-size:24px; } .login-header { margin-bottom:24px; } }
        @media(prefers-reduced-motion:reduce) { .spinner-circle { animation:none; } }
    </style>
</head>
<body>
<div class="login-wrapper">
    <header class="login-header">
        <img src="{{ asset('images/barangay-doyong-logo.png') }}" alt="Barangay Doyong Official Seal" class="login-logo" width="105" height="105"/>
        <h1 class="login-title">BARANGAY DOYONG</h1>
        <p class="login-subtitle">Emergency, Disaster &amp; Records Management System</p>
    </header>
    <main class="login-card" aria-labelledby="loginHeading">
        <h2 class="card-title" id="loginHeading">Log In</h2>
        @if(session('success'))<div class="login-message" role="status">{{ session('success') }}</div>@endif
        @foreach($errors->getMessages() as $field => $messages)
            @if(!in_array($field, ['email','password']))
                @foreach($messages as $message)<div class="login-message login-error" role="alert">{{ $message }}</div>@endforeach
            @endif
        @endforeach
        <form method="POST" action="{{ route('login.post', [], false) }}" class="login-form" id="loginForm">
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">Username or Email</label>
                <input type="text" id="email" name="email" class="form-input" placeholder="Enter your username or email" value="{{ old('email') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required @if($errors->has('email')) aria-invalid="true" aria-describedby="emailError" @endif/>
                <span class="field-error-text" id="emailError" aria-live="polite">{{ $errors->first('email') }}</span>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" autocomplete="current-password" required @if($errors->has('password')) aria-invalid="true" aria-describedby="passwordError" @endif/>
                    <button type="button" class="toggle-password" id="btnTogglePassword" aria-label="Show password" aria-pressed="false" title="Show password">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="eye-slash" d="m3 3 18 18"/></svg>
                    </button>
                </div>
                <span class="field-error-text" id="passwordError" aria-live="polite">{{ $errors->first('password') }}</span>
            </div>
            <label class="remember-username"><input type="checkbox" id="rememberUsername"/>Remember my username</label>
            <button type="submit" class="btn-login" id="btnLogin">
                <span class="btn-content">Log In</span>
                <span class="btn-spinner"><span class="spinner-circle" aria-hidden="true"></span>Authenticating...</span>
            </button>
            <div class="form-options-row"><a href="{{ route('password.request') }}" class="forgot-password">Forgot Password?</a></div>
        </form>
    </main>
    <footer class="login-footer">Barangay Doyong, Calasiao, Pangasinan</footer>
</div>
<script>
(() => {
    const form = document.getElementById('loginForm');
    const identifier = document.getElementById('email');
    const password = document.getElementById('password');
    const remember = document.getElementById('rememberUsername');
    const button = document.getElementById('btnLogin');
    const toggle = document.getElementById('btnTogglePassword');
    form.noValidate = true;
    identifier.maxLength = 255;
    identifier.setAttribute('aria-describedby', 'emailError');
    password.setAttribute('aria-describedby', 'passwordError');
    function validateField(field) {
        const message = field === identifier
            ? (!field.value.trim() ? 'Enter your username or email.' : field.value.length > 255 ? 'Your username or email must not exceed 255 characters.' : '')
            : (!field.value ? 'Enter your password.' : '');
        document.getElementById(field === identifier ? 'emailError' : 'passwordError').textContent = message;
        field.setAttribute('aria-invalid', String(Boolean(message)));
        return !message;
    }
    [identifier, password].forEach(field => {
        field.addEventListener('blur', () => validateField(field));
        field.addEventListener('input', () => {
            if (field.getAttribute('aria-invalid') === 'true') validateField(field);
        });
    });
    const storageKey = 'barangayDoyong.rememberedUsername';
    // Only the explicitly opted-in login identifier is saved; never a password or token.
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            remember.checked = true;
            if (!identifier.value) identifier.value = saved;
        }
    } catch (_) { /* Login remains usable when browser storage is unavailable. */ }
    function saveIdentifier() {
        try {
            if (remember.checked && identifier.value.trim()) localStorage.setItem(storageKey, identifier.value.trim());
            else localStorage.removeItem(storageKey);
        } catch (_) {}
    }
    remember.addEventListener('change', saveIdentifier);
    identifier.addEventListener('input', () => { if (remember.checked) saveIdentifier(); });
    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        toggle.title = visible ? 'Hide password' : 'Show password';
        toggle.setAttribute('aria-pressed', String(visible));
    });
    form.addEventListener('submit', event => {
        if (button.disabled) { event.preventDefault(); return; }
        const identifierValid = validateField(identifier);
        const passwordValid = validateField(password);
        if (!identifierValid || !passwordValid) {
            event.preventDefault();
            (identifierValid ? password : identifier).focus();
            return;
        }
        identifier.value = identifier.value.trim();
        saveIdentifier();
        button.classList.add('is-loading');
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.classList.remove('is-loading');
        form.removeAttribute('aria-busy');
    });
})();
</script>
</body>
</html>
