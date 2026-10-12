/**
 * staff incident management page script
 * Place in: public/js/staff/incidentmanagement.js
 * Load via: <script src="{{ asset('js/staff/incidentmanagement.js') }}" defer></script>
 */

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('incidentModal');
    if (!modal) return;

    var modalClose = document.getElementById('modalClose');
    var modalCancel = document.getElementById('modalCancel');
    var modalIncidentId = document.getElementById('modalIncidentId');
    var modalStatus = document.getElementById('modalStatus');
    var modalIncidentType = document.getElementById('modalIncidentType');
    var modalReporter = document.getElementById('modalReporter');
    var modalLocation = document.getElementById('modalLocation');
    var modalReportedAt = document.getElementById('modalReportedAt');
    var modalDescription = document.getElementById('modalDescription');
    var modalStatusForm = document.getElementById('modalStatusForm');

    function openModal(data) {
        modalIncidentId.value = data.id;
        modalStatus.value = data.status;
        modalIncidentType.textContent = data.type || '—';
        modalReporter.textContent = data.reporter || '—';
        modalLocation.textContent = data.location || '—';
        modalReportedAt.textContent = data.reported_at || '—';
        modalDescription.textContent = data.description || 'No description provided.';

        modalStatusForm.action = '/staff/incidentmanagement/' + data.id + '/status';
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    document.querySelectorAll('.btn-view, .btn-update-status').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var data = {
                id: this.dataset.incidentId,
                type: this.dataset.incidentType,
                reporter: this.dataset.incidentReporter,
                location: this.dataset.incidentLocation,
                status: this.dataset.incidentStatus,
                description: this.dataset.incidentDescription,
                reported_at: this.dataset.incidentReported,
            };
            openModal(data);
        });
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
