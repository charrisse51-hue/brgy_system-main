<section class="dashboard-panel p-6" aria-label="Quick actions">
    <h4 class="font-bold text-slate-800">Quick actions</h4>
    <p class="text-xs text-slate-500 mt-1 mb-5">Keep community records up to date.</p>
    @foreach(['residents' => ['users', 'Resident record', 'Register a barangay resident'], 'emergency' => ['siren', 'Emergency record', 'Document an emergency response'], 'incidents' => ['triangle-alert', 'Disaster record', 'Record a disaster incident']] as $module => [$icon, $label, $description])
        <a class="ui-quick-action" href="{{ route(\App\Support\RecordPortal::routeName($module.'.create')) }}">
            <span class="ui-quick-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
            <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
            <span aria-hidden="true">&rarr;</span>
        </a>
    @endforeach
</section>
