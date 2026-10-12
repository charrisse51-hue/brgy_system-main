<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>User Management &ndash; Barangay Doyong EDRMS</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}"><link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"><link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}"><link rel="stylesheet" href="{{ asset('css/account-management.css') }}">
@include('components.ui-assets')</head><body class="admin-theme user-management"><div class="app-shell">
@include('admin.partials.sidebar')
<main class="main-area"><header class="topbar">@include('components.navigation-toggle')<div class="topbar-title"><h2>User Management</h2><p>Account administration and personnel access</p></div><button type="button" class="btn btn-primary" data-open-create><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Create User Account</button></header>
<div class="page-content">
@if(session('success'))<div class="flash-msg success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="account-errors" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="account-errors" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="account-stats" aria-label="User account totals">
@foreach(['Total User Accounts'=>$totalUsers,'Active Accounts'=>$activeCount,'Inactive Accounts'=>$inactiveCount] as $label=>$count)<div class="account-stat"><p>{{ $label }}</p><strong>{{ number_format($count) }}</strong></div>@endforeach
</section>
<section class="account-table-card"><div class="account-table-heading"><h3>Barangay Personnel</h3><p>System roles and personnel designations</p></div>
<form class="account-filters" method="GET" action="{{ route('admin.users.index') }}"><label class="account-search">Search personnel<input type="search" name="search" maxlength="150" value="{{ request('search') }}" placeholder="Staff name or username"></label><label>Role<select name="role"><option value="">All Roles</option><option value="admin" @selected(request('role')==='admin')>Administrator</option><option value="staff" @selected(request('role')==='staff')>Staff Member</option></select></label><label>Status<select name="status"><option value="">All Statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select></label><button class="btn btn-primary" type="submit">Search</button><a class="btn btn-ghost" href="{{ route('admin.users.index') }}">Clear</a></form>
<div class="account-table-wrap"><table class="data-table account-table"><thead><tr><th>Staff Name</th><th>Username</th><th>Role</th><th>Account Status</th><th>Last Login</th><th>Actions</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td><strong>{{ $user->name }}</strong><small class="account-email">{{ $user->email }}</small></td><td>{{ $user->username ?: 'Not set' }}</td><td>{{ $user->role === 'admin' ? 'Administrator' : 'Staff Member' }}<small class="account-email">Position: {{ $user->position ?: 'Not specified' }}</small></td><td><span class="account-badge {{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $user->last_login_at ? $user->last_login_at->timezone('Asia/Manila')->format('M d, Y g:i A') : 'Not recorded' }}@if($user->last_login_at)<small class="account-email">Philippine time</small>@endif</td><td><div class="account-actions">
<button type="button" class="btn btn-ghost" data-edit-user="{{ json_encode($user->only(['id','name','first_name','middle_name','last_name','username','email','contact_number','role','position','is_active','can_generate_reports'])) }}" data-update-url="{{ route('admin.users.update',$user) }}">Edit</button>
<form method="POST" action="{{ route('admin.users.status',$user) }}" @if($user->is_active) data-account-confirm="Deactivate {{ $user->name }}? Their sessions will end and their records and history will be preserved." @endif>@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}"><button type="submit" class="btn {{ $user->is_active ? 'btn-ghost' : 'btn-primary' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
<form method="POST" action="{{ route('admin.users.password-reset',$user) }}" data-account-confirm="Send a password reset link to {{ $user->email }}?">@csrf<button type="submit" class="btn btn-ghost" @disabled(!$user->is_active)>Reset Password</button></form>
</div></td></tr>
@empty<tr><td colspan="6" class="account-empty">No user accounts match your search or filters.</td></tr>@endforelse
</tbody></table></div><footer class="account-pagination"><span>Showing {{ $users->firstItem() ?? 0 }}&ndash;{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}</span>{{ $users->links() }}</footer></section>
</div></main></div>
@foreach(['create','edit'] as $mode)
<dialog class="account-dialog" id="{{ $mode }}AccountDialog" aria-labelledby="{{ $mode }}AccountTitle"><div class="account-dialog-heading"><div><h2 id="{{ $mode }}AccountTitle">{{ $mode === 'create' ? 'Create User Account' : 'Edit User Account' }}</h2><p data-legacy-name></p></div><button type="button" class="btn btn-ghost" data-close-dialog aria-label="Close">&times;</button></div>
<form class="account-form" method="POST" action="{{ route('admin.users.store') }}" data-account-form="{{ $mode }}">@csrf @if($mode==='edit') @method('PUT') @endif<input type="hidden" name="_account_mode" value="{{ $mode }}"><input type="hidden" name="_account_id"><div class="account-form-grid">
@foreach(['first_name'=>'First Name','middle_name'=>'Middle Name','last_name'=>'Last Name','username'=>'Username','email'=>'Email Address','contact_number'=>'Contact Number'] as $name=>$label)
<label>{{ $label }}<input name="{{ $name }}" type="{{ $name==='email' ? 'email' : ($name==='contact_number' ? 'tel' : 'text') }}" maxlength="{{ in_array($name,['first_name','middle_name','last_name']) ? 100 : ($name==='username' ? 50 : ($name==='contact_number' ? 20 : 255)) }}" @if($mode==='create' && in_array($name,['first_name','last_name','username','email'])) required @endif @if($name==='email') required autocomplete="email" @endif @if($name==='username') minlength="3" pattern="[A-Za-z0-9_.\-]+" autocomplete="username" @endif></label>
@endforeach
@if($mode==='create')
<label>User Role<select name="role" required><option value="staff">Staff Member</option><option value="admin">Administrator</option></select></label>
@else
<label>User Role<input name="role_display" readonly aria-describedby="editRoleHelp"><input type="hidden" name="role"><small id="editRoleHelp">System permissions remain unchanged.</small></label>
@endif
<label>Position / Designation<input type="text" name="position" maxlength="100"></label>
<label>Account Status<select name="is_active" required><option value="1">Active</option><option value="0">Inactive</option></select></label>
<label>Report Access<select name="can_generate_reports" required><option value="1">Authorized</option><option value="0">Not authorized (staff only)</option></select></label>
@if($mode==='create')<label>Password<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><label>Confirm Password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
@else<p class="account-help">Existing passwords are never displayed. Use Reset Password to send an email link.</p>@endif
</div><footer class="account-form-footer"><button type="button" class="btn btn-ghost" data-close-dialog>Cancel</button><button type="submit" class="btn btn-primary">{{ $mode==='create' ? 'Create Account' : 'Save Changes' }}</button></footer></form></dialog>
@endforeach
@php $accountRestore = ['mode'=>old('_account_mode'),'values'=>collect(session()->getOldInput())->except(['password','password_confirmation','_token'])->all(),'updateBase'=>url('/admin/users'),'hasErrors'=>$errors->any()]; @endphp
<script id="account-restore" type="application/json">@json($accountRestore)</script>
<script src="{{ asset('js/account-management.js') }}" defer></script>
</body></html>
