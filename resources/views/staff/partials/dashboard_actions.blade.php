<section class="staff-actions" aria-labelledby="staff-actions-title">
    <h2 id="staff-actions-title">Quick Actions</h2>
    <p>Start a new record</p>
    @foreach([
        ['staff.incidents.create', 'report_problem', 'Disaster Record', 'Document a disaster incident'],
        ['staff.emergency.create', 'notification_important', 'Emergency Record', 'Record an emergency response'],
        ['staff.residents.create', 'person_add', 'Resident Record', 'Register a barangay resident'],
    ] as [$destination, $icon, $label, $description])
        <a class="staff-action-link" href="{{ route($destination) }}">
            <span class="staff-action-icon material-icons-round" aria-hidden="true">{{ $icon }}</span>
            <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
            <span class="material-icons-round staff-action-arrow" aria-hidden="true">arrow_forward</span>
        </a>
    @endforeach
</section>
