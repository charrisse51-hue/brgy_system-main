@once
<link rel="stylesheet" href="{{ asset('css/my-profile.css') }}">
<script src="{{ asset('js/account-menu.js') }}" defer></script>
@endonce
<div class="sidebar-user">
    <details class="account-menu">
        <summary class="sidebar-user-card" aria-label="Open account menu">
            <span class="user-avatar" aria-hidden="true"><i data-lucide="user"></i></span>
            <span class="user-info"><span class="user-name">{{ auth()->user()->name }}</span><span class="user-role">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Staff Member' }}</span>@if(auth()->user()->position)<span class="user-role">{{ auth()->user()->position }}</span>@endif</span>
            <i data-lucide="chevron-right" class="account-chevron" aria-hidden="true"></i>
        </summary>
        <div class="account-menu-options">
            <a href="{{ route('account.show') }}"><i data-lucide="user" aria-hidden="true"></i><span>My Profile</span></a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="account-logout"><i data-lucide="log-out" aria-hidden="true"></i><span>Log Out</span></button></form>
        </div>
    </details>
</div>
