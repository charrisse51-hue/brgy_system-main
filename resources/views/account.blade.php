<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Profile – Barangay Doyong</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/disaster-records.css') }}">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
@include('components.ui-assets')
<link rel="stylesheet" href="{{ asset('css/my-profile.css') }}">
<script src="{{ asset('js/my-profile.js') }}" defer></script>
</head><body class="admin-theme disaster-records my-profile">
<div class="app-shell">
@include('admin.partials.sidebar')
<main class="main-area">
<header class="topbar">@include('components.navigation-toggle')<div class="topbar-title"><h2>My Profile</h2><p>Manage your account information and password.</p></div></header>
<div class="page-content">
@if(session('profile_success') || session('password_success'))
<div class="profile-success" role="status" data-profile-success>{{ session('password_success') ?: session('profile_success') }}</div>
@endif
<div class="profile-panels">
<section class="profile-panel" aria-labelledby="personalHeading">
<h3 id="personalHeading">Personal Information</h3>
<div class="profile-identity"><div class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div><div><strong>{{ $user->name }}</strong><p>{{ $user->role === 'admin' ? 'Administrator' : 'Staff Member' }}</p></div></div>
<form method="POST" action="{{ route('account.update') }}" data-profile-form>
@csrf @method('PATCH')
@if(!$user->first_name && !$user->last_name)<p class="profile-hint">Your original full name is preserved. Enter both first and last names only if you want to update it.</p>@endif
<div class="profile-fields">
@foreach(['first_name'=>'First Name','middle_name'=>'Middle Name','last_name'=>'Last Name','contact_number'=>'Contact Number'] as $field=>$label)
<div class="profile-field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $user->$field) }}" maxlength="{{ $field === 'contact_number' ? 20 : 100 }}" @if($field === 'contact_number') type="tel" autocomplete="tel" @else type="text" autocomplete="{{ ['first_name'=>'given-name','middle_name'=>'additional-name','last_name'=>'family-name'][$field] }}" @endif @if($errors->profileUpdate->has($field)) aria-invalid="true" @endif aria-describedby="{{ $field }}Error"><span class="profile-error" id="{{ $field }}Error">{{ $errors->profileUpdate->first($field) }}</span></div>
@endforeach
<div class="profile-field"><label for="profileUsername">Username</label><input id="profileUsername" value="{{ $user->username ?: 'Not set' }}" readonly></div>
<div class="profile-field"><label for="profileEmail">Email Address</label><input id="profileEmail" value="{{ $user->email ?: 'Not set' }}" readonly></div>
<div class="profile-field profile-wide"><label for="profileRole">Assigned Role</label><input id="profileRole" value="{{ $user->role === 'admin' ? 'Administrator' : 'Staff Member' }}" readonly></div>
<div class="profile-field profile-wide"><label for="profilePosition">Position / Designation</label><input id="profilePosition" value="{{ $user->position ?: 'Not specified' }}" readonly></div>
</div>
<p class="profile-hint">Your username, email, role, and position are managed through User Management.</p>
<button type="submit" class="profile-button">Save Personal Information</button>
</form>
</section>
<section class="profile-panel" id="change-password" aria-labelledby="passwordHeading">
<h3 id="passwordHeading">Change Password</h3>
<p class="profile-hint">Use at least 12 characters. Spaces and special characters are welcome. Choose a unique password or a strong passphrase.</p>
<form method="POST" action="{{ route('account.password') }}" id="changePasswordForm">
@csrf @method('PUT')
@foreach(['current_password'=>'Current Password','password'=>'New Password','password_confirmation'=>'Confirm New Password'] as $field=>$label)
<div class="profile-field password-field"><label for="{{ $field }}">{{ $label }} <span aria-hidden="true">*</span></label>
<div class="profile-password-wrap"><input type="password" id="{{ $field }}" name="{{ $field }}" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required @if($field === 'password') minlength="12" @endif aria-describedby="{{ $field }}Error" @if($errors->passwordUpdate->has($field)) aria-invalid="true" @endif>
<button type="button" class="profile-eye" data-password-target="{{ $field }}" aria-label="Show {{ strtolower($label) }}" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="eye-slash" d="m3 3 18 18"/></svg></button></div>
<span class="profile-error" id="{{ $field }}Error" aria-live="polite">{{ $errors->passwordUpdate->first($field) }}</span></div>
@endforeach
<p class="profile-hint">After updating, your other signed-in sessions will end. This session will remain signed in.</p>
<button type="submit" class="profile-button">Update Password</button>
</form>
</section>
</div>
</div></main></div>
</body></html>
