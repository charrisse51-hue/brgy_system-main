<?php

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows staff emergency form fields and searches sources and record IDs', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    $record = Alert::create([
        'created_by' => $staff->id, 'is_record' => true,
        'title' => 'Flood report', 'message' => 'Flood narrative', 'level' => 'low',
        'report_source' => 'Barangay hotline', 'report_date_time' => '2026-09-22 14:30:00',
        'emergency_type' => 'flood', 'location' => 'Purok 3',
        'incident_date' => '2026-09-21', 'incident_time' => '09:15',
        'reporter_name' => 'Maria Reporter', 'verification_status' => 'pending',
    ]);
    $this->actingAs($staff)->get('/staff/emergency')->assertOk()
        ->assertSee('Report Date &amp; Time', false)->assertSee('Report Source')
        ->assertSee('Incident Date &amp; Time', false)->assertSee('Verification Status')
        ->assertSee('Barangay hotline')->assertSee('Maria Reporter')->assertSee('Purok 3')
        ->assertSee('Sep 22, 2026')->assertSee('Sep 21, 2026')->assertSee('09:15')
        ->assertDontSee('Date Submitted')->assertDontSee('Response Action')
        ->assertSee(route('staff.emergency.edit', $record));
    foreach (['Barangay hotline', '#EMG-'.str_pad($record->id, 4, '0', STR_PAD_LEFT)] as $search) {
        $this->get('/staff/emergency?'.http_build_query(['search' => $search]))
            ->assertOk()->assertSee('Maria Reporter');
    }
    $this->get('/staff/emergency?search=NoSuchSource')->assertOk()->assertSee('No emergency records found.');
});

it('keeps legacy alerts readable in the staff emergency table', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Alert::create(['created_by' => $staff->id, 'title' => 'Legacy advisory', 'message' => 'Old alert', 'level' => 'high']);
    $this->actingAs($staff)->get('/staff/emergency')->assertOk()
        ->assertSee('Legacy advisory')->assertSee('Not specified')->assertSee('Not applicable');
});
