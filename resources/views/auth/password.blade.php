<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset Password – Barangay Doyong</title><link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"><link rel="stylesheet" href="{{ asset('css/account-management.css') }}"></head>
<body class="account-reset"><main class="account-reset-card"><h1>{{ $reset ? 'Choose a new password' : 'Reset your password' }}</h1>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div class="account-errors" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route($reset ? 'password.update' : 'password.email') }}">@csrf
@if($reset)<input type="hidden" name="token" value="{{ $token }}">@endif
<label>Email address<input type="email" name="email" value="{{ old('email', $email ?? '') }}" autocomplete="email" required></label>
@if($reset)<label>New password<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><label>Confirm password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>@endif
<button class="btn btn-primary" type="submit">{{ $reset ? 'Save new password' : 'Send reset link' }}</button><a href="{{ route('login') }}">Back to sign in</a>
</form></main></body></html>
