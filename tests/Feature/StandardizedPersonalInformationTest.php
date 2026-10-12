<?php

use App\Models\{Alert, Incident, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const ALLOWED_SITIOS = [
    'Sitio Cabuaan',
    'Sitio Camanggaan',
    'Sitio Gonzales',
    'Sitio Basasong',
    'Sitio Doyong Centro',
    'Sitio Baba',
    'Sitio Central',
];

function validDisasterData(array $overrides = []): array
{
    return array_merge([
        'type' => 'flood',
        'location' => 'Purok 1, Sitio Cabuaan',
        'description' => 'Floodwaters rising rapidly due to prolonged rain.',
        'record_date' => now()->toDateString(),
        'record_time' => '10:00',
        'complainant_first_name' => 'Maria-Elena',
        'complainant_middle_name' => 'Santos',
        'complainant_last_name' => 'Dela Cruz',
        'complainant_age' => 34,
        'sitio' => 'Sitio Cabuaan',
        'other_address' => 'Near Barangay Hall',
        'incident_date' => now()->subDay()->toDateString(),
        'incident_time' => '09:00',
    ], $overrides);
}

function validEmergencyData(array $overrides = []): array
{
    return array_merge([
        'report_source' => 'Barangay Hotline',
        'report_date_time' => now()->format('Y-m-d\TH:i'),
        'report_type' => 'initial',
        'emergency_type' => 'flood',
        'location' => 'Sitio Central',
        'incident_date' => now()->subDay()->toDateString(),
        'incident_time' => '08:30',
        'message' => 'Flooding in residential areas along the road.',
        'verification_status' => 'pending',
        'reporter_first_name' => 'Juan',
        'reporter_middle_name' => '',
        'reporter_last_name' => 'Dela Cruz',
        'reporter_age' => 45,
        'reporter_sitio' => 'Sitio Central',
        'reporter_other_address' => 'Opposite Basketball Court',
        'reporter_contact' => '09123456789',
    ], $overrides);
}

it('renders standardized personal information fields and sitio dropdown in Disaster form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/incidents/create');
    $response->assertOk();
    $response->assertSee('complainant_first_name');
    $response->assertSee('complainant_middle_name');
    $response->assertSee('complainant_last_name');
    $response->assertSee('sitio');
    $response->assertSee('Select Sitio');
    foreach (ALLOWED_SITIOS as $sitio) {
        $response->assertSee($sitio);
    }
    // Barangay is hidden, not a visible input
    $response->assertSee('type="hidden" id="barangay" name="barangay" value="Doyong"', false);
});

it('renders standardized personal information fields and sitio dropdown in Emergency form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/emergency/create');
    $response->assertOk();
    $response->assertSee('reporter_first_name');
    $response->assertSee('reporter_middle_name');
    $response->assertSee('reporter_last_name');
    $response->assertSee('reporter_sitio');
    $response->assertSee('Select Sitio');
    foreach (ALLOWED_SITIOS as $sitio) {
        $response->assertSee($sitio);
    }
});

it('rejects empty required names in Disaster form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $data = validDisasterData([
        'complainant_first_name' => '',
        'complainant_last_name' => '',
    ]);

    $this->actingAs($admin)->from('/admin/incidents/create')
        ->post('/admin/incidents', $data)
        ->assertSessionHasErrors(['complainant_first_name', 'complainant_last_name']);
});

it('rejects numbers and invalid special characters in name fields for both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Disaster form with numbers in first name and special characters in last name
    $badDisaster = validDisasterData([
        'complainant_first_name' => 'Maria123',
        'complainant_last_name' => 'Santos@#$',
    ]);

    $this->actingAs($admin)->post('/admin/incidents', $badDisaster)
        ->assertSessionHasErrors(['complainant_first_name', 'complainant_last_name']);

    // Emergency form with numbers in reporter name
    $badEmergency = validEmergencyData([
        'reporter_first_name' => 'Juan99',
        'reporter_last_name' => 'Cruz!',
    ]);

    $this->actingAs($admin)->post('/admin/emergency', $badEmergency)
        ->assertSessionHasErrors(['reporter_first_name', 'reporter_last_name']);
});

it('rejects names shorter than 2 characters in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $shortDisaster = validDisasterData([
        'complainant_first_name' => 'M',
        'complainant_last_name' => 'S',
    ]);
    $this->actingAs($admin)->post('/admin/incidents', $shortDisaster)
        ->assertSessionHasErrors(['complainant_first_name', 'complainant_last_name']);

    $shortEmergency = validEmergencyData([
        'reporter_first_name' => 'J',
        'reporter_last_name' => 'C',
    ]);
    $this->actingAs($admin)->post('/admin/emergency', $shortEmergency)
        ->assertSessionHasErrors(['reporter_first_name', 'reporter_last_name']);
});

it('accepts valid Filipino names with spaces hyphens and apostrophes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $validData = validDisasterData([
        'complainant_first_name' => 'Mary-Ann Niño',
        'complainant_middle_name' => 'Dela Rosa',
        'complainant_last_name' => "O'Connor-Santos",
    ]);

    $this->actingAs($admin)->post('/admin/incidents', $validData)
        ->assertSessionHasNoErrors();

    $incident = Incident::latest()->first();
    expect($incident->logbook['complainant_first_name'])->toBe('Mary-Ann Niño')
        ->and($incident->logbook['complainant_last_name'])->toBe("O'Connor-Santos")
        ->and($incident->logbook['complainant_name'])->toBe("Mary-Ann Niño Dela Rosa O'Connor-Santos");
});

it('allows leaving middle name blank in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $data = validDisasterData(['complainant_middle_name' => '']);
    $this->actingAs($admin)->post('/admin/incidents', $data)->assertSessionHasNoErrors();

    $incident = Incident::latest()->first();
    expect($incident->logbook['complainant_middle_name'])->toBeNull()
        ->and($incident->logbook['complainant_name'])->toBe('Maria-Elena Dela Cruz');

    $emgData = validEmergencyData(['reporter_middle_name' => '']);
    $this->actingAs($admin)->post('/admin/emergency', $emgData)->assertSessionHasNoErrors();

    $alert = Alert::latest()->first();
    expect($alert->reporter_middle_name)->toBeNull()
        ->and($alert->reporter_name)->toBe('Juan Dela Cruz');
});

it('rejects invalid ages outside 0-120 or negative numbers in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData(['complainant_age' => -5]))
        ->assertSessionHasErrors(['complainant_age']);

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData(['complainant_age' => 150]))
        ->assertSessionHasErrors(['complainant_age']);

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData(['reporter_age' => -1]))
        ->assertSessionHasErrors(['reporter_age']);

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData(['reporter_age' => 130]))
        ->assertSessionHasErrors(['reporter_age']);
});

it('rejects future occurrence dates and times in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $futureDate = now()->addDays(2)->toDateString();

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData([
        'incident_date' => $futureDate,
        'incident_time' => '12:00',
    ]))->assertSessionHasErrors(['incident_time']);

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData([
        'incident_date' => $futureDate,
        'incident_time' => '12:00',
    ]))->assertSessionHasErrors(['incident_time']);
});

it('rejects arbitrary sitio values not included in the seven options in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData(['sitio' => 'Sitio Nowhere']))
        ->assertSessionHasErrors(['sitio']);

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData(['reporter_sitio' => 'Fake Sitio 123']))
        ->assertSessionHasErrors(['reporter_sitio']);
});

it('accepts each of the seven allowed sitio options in both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (ALLOWED_SITIOS as $sitio) {
        $this->actingAs($admin)->post('/admin/incidents', validDisasterData(['sitio' => $sitio]))
            ->assertSessionHasNoErrors();

        $incident = Incident::orderByDesc('id')->first();
        expect($incident->logbook['sitio'])->toBe($sitio)
            ->and($incident->logbook['barangay'])->toBe('Doyong');

        $this->actingAs($admin)->post('/admin/emergency', validEmergencyData(['reporter_sitio' => $sitio]))
            ->assertSessionHasNoErrors();

        $alert = Alert::orderByDesc('id')->first();
        expect($alert->reporter_sitio)->toBe($sitio);
    }
});

it('displays saved personal information correctly on details view for both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData([
        'complainant_first_name' => 'Clara',
        'complainant_middle_name' => 'Bautista',
        'complainant_last_name' => 'Ramos',
        'sitio' => 'Sitio Doyong Centro',
        'other_address' => 'Near Chapel',
        'complainant_age' => 42,
    ]))->assertSessionHasNoErrors();

    $incident = Incident::latest()->first();
    $resp = $this->get('/admin/incidents/'.$incident->id);
    $resp->assertOk();
    $resp->assertSee('Clara Bautista Ramos');
    $resp->assertSee('Sitio Doyong Centro');
    $resp->assertSee('Near Chapel');
    $resp->assertSee('42');

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData([
        'reporter_first_name' => 'Mateo',
        'reporter_middle_name' => 'Rivera',
        'reporter_last_name' => 'Aquino',
        'reporter_sitio' => 'Sitio Basasong',
        'reporter_other_address' => 'Purok Basasong East',
        'reporter_age' => 50,
    ]))->assertSessionHasNoErrors();

    $alert = Alert::latest()->first();
    $respEmg = $this->get('/admin/emergency/'.$alert->id);
    $respEmg->assertOk();
    $respEmg->assertSee('Mateo');
    $respEmg->assertSee('Aquino');
    $respEmg->assertSee('Sitio Basasong');
    $respEmg->assertSee('50');
});

it('edits and updates personal information fields on both forms', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/admin/incidents', validDisasterData())->assertSessionHasNoErrors();
    $incident = Incident::latest()->first();

    $this->get('/admin/incidents/'.$incident->id.'/edit')->assertOk()
        ->assertSee('value="Maria-Elena"', false)
        ->assertSee('value="Dela Cruz"', false);

    $this->put('/admin/incidents/'.$incident->id, validDisasterData([
        'complainant_first_name' => 'UpdatedFirst',
        'complainant_last_name' => 'UpdatedLast',
        'sitio' => 'Sitio Basasong',
    ]))->assertSessionHasNoErrors();

    $incident->refresh();
    expect($incident->logbook['complainant_first_name'])->toBe('UpdatedFirst')
        ->and($incident->logbook['complainant_last_name'])->toBe('UpdatedLast')
        ->and($incident->logbook['sitio'])->toBe('Sitio Basasong');

    $this->actingAs($admin)->post('/admin/emergency', validEmergencyData())->assertSessionHasNoErrors();
    $alert = Alert::latest()->first();

    $this->get('/admin/emergency/'.$alert->id.'/edit')->assertOk()
        ->assertSee('value="Juan"', false)
        ->assertSee('value="Dela Cruz"', false);

    $this->put('/admin/emergency/'.$alert->id.'/record', validEmergencyData([
        'reporter_first_name' => 'Renato',
        'reporter_last_name' => 'Soriano',
        'reporter_sitio' => 'Sitio Baba',
    ]))->assertSessionHasNoErrors();

    $alert->refresh();
    expect($alert->reporter_first_name)->toBe('Renato')
        ->and($alert->reporter_last_name)->toBe('Soriano')
        ->and($alert->reporter_sitio)->toBe('Sitio Baba')
        ->and($alert->reporter_name)->toBe('Renato Soriano');
});
