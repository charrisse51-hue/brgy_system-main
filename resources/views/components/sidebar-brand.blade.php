@props([
    'subtitle' => 'RECORDS ADMIN',
    'title' => 'Brgy. Doyong',
    'logo' => asset('images/barangay-doyong-logo.png'),
    'alt' => 'Barangay Doyong logo',
])

@php
    $brandSubtitle = $subtitle ?? ($sidebarSubtitle ?? 'RECORDS ADMIN');
    $brandTitle = $title ?? 'Brgy. Doyong';
    $brandLogo = $logo ?? asset('images/barangay-doyong-logo.png');
    $brandAlt = $alt ?? 'Barangay Doyong logo';
@endphp

<div class="sidebar-brand">
    <img class="sidebar-brand-logo" src="{{ $brandLogo }}"
         alt="{{ $brandAlt }}" width="44" height="44"/>
    <div class="sidebar-brand-text">
        <h1 class="sidebar-brand-title">{{ $brandTitle }}</h1>
        <p class="sidebar-brand-subtitle">{{ $brandSubtitle }}</p>
    </div>
</div>

