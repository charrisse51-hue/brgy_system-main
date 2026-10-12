<?php

use App\Models\{Incident, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function logbookPayload(): array
{
    return [
        'type'=>'flood', 'location'=>'Purok 2', 'description'=>'Water entered the reporting person’s home.',
        'record_date'=>'2026-09-20', 'record_time'=>'13:59', 'complainant_name'=>'Ana Santos',
        'complainant_age'=>32, 'purok'=>'Purok 2', 'barangay'=>'Doyong', 'other_address'=>'Main Street',
        'persons'=>[['name'=>'Juan Cruz', 'age'=>40, 'address'=>'Doyong'], ['name'=>'Maria Cruz', 'age'=>38, 'address'=>'Doyong']],
        'incident_date'=>'2026-09-19', 'incident_time'=>'12:30', 'action_taken'=>'Assistance provided.',
        'meeting_date'=>'2026-09-22', 'meeting_time'=>'09:00', 'remarks'=>'For follow-up.', 'officer'=>'Officer Santos', 'position'=>'Barangay Captain',
    ];
}

it('renders and saves the complete logbook without a photo and uses the signed-in recorder', function () {
    $user = User::factory()->create(['role'=>'admin']);
    $this->actingAs($user)->get('/admin/incidents/create')->assertOk()->assertSee('Complainant / Reporting Person')->assertSee('Add Another Person');
    $this->post('/admin/incidents', logbookPayload() + ['recorded_by'=>'Forged name', 'user_id'=>999, 'record_number'=>'FAKE'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.incidents.index'));
    $incident = Incident::firstOrFail();
    expect($incident->user_id)->toBe($user->id)
        ->and($incident->logbook['recorded_by'])->toBe($user->name)
        ->and($incident->logbook['persons'])->toHaveCount(2)
        ->and($incident->logbook['action_taken'])->toBe('Assistance provided.')
        ->and($incident->record_number)->toStartWith('DR-');
    $this->get('/admin/incidents/'.$incident->id)->assertOk()->assertSee('Maria Cruz')->assertSee('Officer Santos')->assertSee('For follow-up.');
});

it('rejects missing required fields, invalid ages and incomplete meeting schedules', function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    $data = logbookPayload();
    $data['complainant_name'] = '';
    $data['persons'][0]['age'] = -1;
    $data['incident_time'] = '25:99';
    $data['meeting_date'] = '';
    $this->from('/admin/incidents/create')->post('/admin/incidents', $data)->assertSessionHasErrors(['complainant_name','persons.0.age','incident_time','meeting_date']);
    expect(Incident::count())->toBe(0);
});
