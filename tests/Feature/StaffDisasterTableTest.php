<?php

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows saved logbook fields and searches staff disaster records', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $incident = Incident::create([
        'user_id' => $staff->id, 'type' => 'flood', 'location' => 'Purok 3',
        'status' => 'pending', 'archived' => false,
        'logbook' => [
            'record_date' => '2026-09-22', 'record_time' => '14:30',
            'incident_date' => '2026-09-21', 'incident_time' => '09:15',
            'complainant_name' => 'Maria Reporting', 'recorded_by' => 'Original Recorder',
        ],
    ]);
    $this->actingAs($staff)->get('/staff/disaster')->assertOk()
        ->assertSee('Record Date &amp; Time', false)->assertSee('Complainant / Reporting Person')
        ->assertSee('Maria Reporting')->assertSee('Original Recorder')
        ->assertSee('2026-09-22')->assertSee('14:30')
        ->assertSee('2026-09-21')->assertSee('09:15')
        ->assertDontSee('Severity Level')->assertDontSee('Affected Residents')->assertDontSee('Rescued');
    foreach (['Maria Reporting', 'Original Recorder', $incident->record_number] as $search) {
        $this->get('/staff/disaster?'.http_build_query(['search' => $search]))
            ->assertOk()->assertSee('Maria Reporting');
    }
    $this->get('/staff/disaster?search=NoSuchPerson')->assertOk()->assertSee('No disaster records found');
});

it('shows older staff records without logbook data', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Incident::create(['user_id' => $staff->id, 'type' => 'fire', 'location' => 'Old Location', 'status' => 'pending', 'archived' => false]);
    $this->actingAs($staff)->get('/staff/disaster')->assertOk()
        ->assertSee('Old Location')->assertSee('Not specified')->assertSee($staff->name);
});
