@php
    // This partial expects an $alerts paginator or collection.
    // It renders exactly the HTML that appears inside the `#alerts_feed` container
    // on the main community alerts page so that the JS auto-refresh can swap it
    // out cleanly when new data arrives.
@endphp

@forelse($alerts as $alert)
    @php
        $severity = strtolower($alert->severity ?? 'info');
        $iconMap = [
            'fire'     => 'local_fire_department',
            'flood'    => 'water_drop',
            'power'    => 'bolt',
            'weather'  => 'thunderstorm',
            'medical'  => 'medical_services',
            'crime'    => 'gpp_bad',
            'typhoon'  => 'cyclone',
            'default'  => 'campaign',
        ];
        $type = strtolower($alert->type ?? 'default');
        $icon = $iconMap[$type] ?? $iconMap['default'];
        $timeAgo = $alert->created_at
            ? $alert->created_at->diffForHumans()
            : '—';
    @endphp

    <div class="alert-card alert-card-{{ $severity }}">
        <div class="alert-card-inner">
            <div class="alert-card-top">
                <div class="alert-card-left">
                    <div class="alert-icon-wrap alert-icon-{{ $severity }}">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <div>
                        <div class="alert-meta">
                            <span class="severity-badge badge-{{ $severity }}">
                                {{ ucfirst($severity) }} Severity
                            </span>
                            <span class="alert-timestamp">• {{ $timeAgo }}</span>
                        </div>
                        <h4 class="alert-card-title">{{ $alert->title }}</h4>
                    </div>
                </div>
            </div>
            <p class="alert-description">{{ $alert->message }}</p>
            <div class="alert-location">
                <span class="material-symbols-outlined">location_on</span>
                {{ $alert->location ?? 'Barangay Doyong' }}
            </div>
        </div>
    </div>

@empty
    <div class="empty-state">
        <div class="empty-icon-wrap">
            <span class="material-symbols-outlined">campaign</span>
        </div>
        <p class="empty-title">No active alerts</p>
        <p class="empty-sub">There are currently no community alerts. Stay safe!</p>
    </div>
@endforelse
