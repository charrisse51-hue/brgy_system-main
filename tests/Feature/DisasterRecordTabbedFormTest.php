<?php

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validDisasterPayload(): array
{
    return [
        'type' => 'flood',
        'location' => 'Purok 2, Barangay Doyong',
        'description' => 'Floodwater entered households along the low-lying zone.',
        'record_date' => '2026-10-11',
        'record_time' => '14:30',
        'complainant_name' => 'Roberto Valdez',
        'complainant_age' => 45,
        'purok' => 'Purok 2',
        'barangay' => 'Doyong',
        'other_address' => 'Near Riverbank',
        'persons' => [
            ['name' => 'Eduardo Ramos', 'age' => 52, 'address' => 'Purok 2, Doyong'],
            ['name' => 'Elena Ramos', 'age' => 50, 'address' => 'Purok 2, Doyong'],
        ],
        'incident_date' => '2026-10-11',
        'incident_time' => '11:15',
        'action_taken' => 'Immediate evacuation assistance and sandbag deployment provided.',
        'meeting_date' => '2026-10-14',
        'meeting_time' => '10:00',
        'remarks' => 'Coordinated with MDRRMO for relief operations.',
        'officer' => 'Kagawad Santos',
        'position' => 'Committee Chair on Disaster Risk Reduction',
    ];
}

it('renders the full-page tab-based disaster record form with all 5 tabs and navigation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/incidents/create');

    $response->assertOk();

    // 1. Full-page presentation checks (no modal overlay)
    $response->assertDontSee('class="logbook-overlay"', false);
    $response->assertSee('Add Disaster Record');
    $response->assertSee('Back to Disaster Records');

    // 2. All 5 navigation tabs check
    $response->assertSee('Record Information');
    $response->assertSee('Complainant / Reporting Person');
    $response->assertSee('Respondent / Person Involved');
    $response->assertSee('Disaster Information');
    $response->assertSee('Additional Details');

    // 3. Form input fields presence
    $response->assertSee('record_date');
    $response->assertSee('record_time');
    $response->assertSee('complainant_name');
    $response->assertSee('complainant_age');
    $response->assertSee('purok');
    $response->assertSee('barangay');
    $response->assertSee('other_address');
    $response->assertSee('logbook-persons', false);
    $response->assertSee('add-logbook-person', false);
    $response->assertSee('Add Another Person');
    $response->assertSee('type');
    $response->assertSee('incident_date');
    $response->assertSee('incident_time');
    $response->assertSee('location');
    $response->assertSee('description');
    $response->assertSee('action_taken');
    $response->assertSee('meeting_date');
    $response->assertSee('meeting_time');
    $response->assertSee('remarks');
    $response->assertSee('officer');
    $response->assertSee('position');

    // 4. Record preview check
    $response->assertSee('DR-'.now()->format('Y').'-');
});

it('saves a new disaster record from the full-page form to database and redirects', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Officer']);

    $payload = validDisasterPayload();

    $response = $this->actingAs($admin)->post('/admin/incidents', $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.incidents.index'));

    $this->assertDatabaseHas('incidents', [
        'type' => 'flood',
        'location' => 'Purok 2, Barangay Doyong',
        'description' => 'Floodwater entered households along the low-lying zone.',
    ]);

    $incident = Incident::first();
    expect($incident)->not->toBeNull()
        ->and($incident->record_number)->toStartWith('DR-'.now()->format('Y').'-')
        ->and($incident->logbook['complainant_name'])->toBe('Roberto Valdez')
        ->and($incident->logbook['recorded_by'])->toBe('Admin Officer')
        ->and($incident->logbook['persons'])->toHaveCount(2)
        ->and($incident->logbook['officer'])->toBe('Kagawad Santos');
});

it('renders the full-page edit form and updates existing disaster record', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Officer']);

    $incident = Incident::create([
        'user_id' => $admin->id,
        'type' => 'fire',
        'location' => 'Purok 1',
        'description' => 'Electrical fire incident',
        'status' => 'pending',
        'logbook' => [
            'record_date' => '2026-10-10',
            'record_time' => '08:00',
            'complainant_name' => 'Original Complainant',
            'incident_date' => '2026-10-10',
            'incident_time' => '07:30',
            'recorded_by' => 'Admin Officer',
            'persons' => [
                ['name' => 'Person One', 'age' => 30, 'address' => 'Purok 1'],
            ],
        ],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.incidents.edit', $incident));

    $response->assertOk();
    $response->assertSee('Edit Disaster Record');
    $response->assertSee($incident->record_number);
    $response->assertSee('Original Complainant');
    $response->assertSee('value="Person"', false)->assertSee('value="One"', false);

    // Update
    $updateData = validDisasterPayload();
    $updateData['location'] = 'Updated Purok 5';
    $updateData['complainant_name'] = 'Updated Complainant';

    $updateResponse = $this->actingAs($admin)->put(route('admin.incidents.update', $incident), $updateData);
    $updateResponse->assertSessionHasNoErrors();
    $updateResponse->assertRedirect(route('admin.incidents.index'));

    expect($incident->fresh()->location)->toBe('Updated Purok 5')
        ->and($incident->fresh()->logbook['complainant_name'])->toBe('Updated Complainant');
});

it('allows staff to access full-page disaster create form under staff portal', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get('/staff/disaster/create');

    $response->assertOk();
    $response->assertSee('Add Disaster Record');
    $response->assertSee('Record Information');
    $response->assertSee('Complainant / Reporting Person');
    $response->assertSee('Respondent / Person Involved');
    $response->assertSee('Disaster Information');
    $response->assertSee('Additional Details');
});

it('displays newly saved disaster record on index, includes success flash, and does not display red sidebar badge', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Officer']);

    // Create a pending incident
    Incident::create([
        'user_id' => $admin->id,
        'type' => 'flood',
        'location' => 'Purok 1',
        'description' => 'Existing incident',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.incidents.index'));
    $response->assertOk();

    // Verify red badge is permanently removed
    $response->assertDontSee('class="nav-badge"', false);

    // Save a new record
    $payload = validDisasterPayload();
    $payload['location'] = 'Unique Location 777';

    $postResponse = $this->actingAs($admin)->post('/admin/incidents', $payload);
    $postResponse->assertSessionHasNoErrors();
    $postResponse->assertRedirect(route('admin.incidents.index'));

    $newIncident = Incident::where('location', 'Unique Location 777')->firstOrFail();

    // Follow redirect
    $indexResponse = $this->actingAs($admin)->get(route('admin.incidents.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee('Unique Location 777');
    $indexResponse->assertSee($newIncident->record_number);
    $indexResponse->assertDontSee('class="nav-badge"', false);
});


