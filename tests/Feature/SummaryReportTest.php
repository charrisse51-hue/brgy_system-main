<?php

use App\Models\{User, Resident};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restricts summaries to administrators', function () {
    $this->get('/admin/summary-reports')->assertRedirect('/login');
    $this->actingAs(User::factory()->create(['role'=>'resident']))->get('/admin/summary-reports')->assertForbidden();
});

it('filters report totals and exports the same CSV totals', function (string $role) {
    $admin = User::factory()->create(['role'=>$role]);
    $path = '/'.$role.'/summary-reports';
    Resident::create(['name'=>'Test Resident', 'address'=>'Test address', 'first_name'=>'Test', 'last_name'=>'Resident', 'status'=>'active', 'zone'=>'zone1']);
    $this->actingAs($admin)->get($path.'?category=residents')->assertOk()->assertViewHas('sections', fn ($sections) => count($sections) === 1 && $sections[0]['total'] === 1);
    $this->get($path.'?category=residents&to=2000-01-01')->assertOk()->assertViewHas('sections', fn ($sections) => $sections[0]['total'] === 0);
    $response = $this->get($path.'?category=residents&format=csv')->assertOk();
    expect($response->streamedContent())->toContain('"Resident Records",Total,"All records",1');
    $this->get($path.'?from=2026-09-18&to=2026-09-01')->assertSessionHasErrors('to');
})->with(['admin', 'staff']);

it('uses staff navigation and staff report links', function () {
    $this->actingAs(User::factory()->create(['role' => 'staff']));
    $this->get('/staff/staffdashboard')->assertOk()->assertSee('Summary Reports')
        ->assertSee(route('staff.summary-reports.index'));
    $this->get('/staff/summary-reports')->assertOk()->assertSee('RECORDS STAFF')
        ->assertSee('aria-current="page"', false)
        ->assertSee('action="'.route('staff.summary-reports.index').'"', false)
        ->assertDontSee('href="'.url('/admin/'), false)
        ->assertDontSee('User Management')->assertDontSee('System Settings');
    $this->get('/admin/summary-reports')->assertForbidden();
});

it('protects staff summaries from guests and residents', function () {
    $this->get('/staff/summary-reports')->assertRedirect('/login');
    $this->actingAs(User::factory()->create(['role' => 'resident']))
        ->get('/staff/summary-reports')->assertForbidden();
});

it('generates monthly incident reports and exports monthly CSV', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    \App\Models\Incident::create([
        'user_id' => $admin->id,
        'type' => 'flood',
        'location' => 'Sitio Central',
        'description' => 'Heavy rainfall caused ankle-deep flooding.',
        'status' => 'resolved',
        'logbook' => [
            'incident_date' => '2026-10-12',
            'location' => 'Sitio Central',
        ],
    ]);

    $response = $this->get('/admin/summary-reports?report_type=monthly_incident&month=10&year=2026')
        ->assertOk()
        ->assertSee('Heavy rainfall caused ankle-deep flooding')
        ->assertSee('Sitio Central')
        ->assertSee('BARANGAY DOYONG');

    $csv = $this->get('/admin/summary-reports?report_type=monthly_incident&month=10&year=2026&format=csv')
        ->assertOk();
    expect($csv->streamedContent())
        ->toContain('Day,Date,Type,"Place / Location","Incident / Disaster Details","Remarks / Status"')
        ->toContain('Heavy rainfall caused ankle-deep flooding');
});

it('removes unnecessary paper size dropdown, signatory inputs, and preview toolbar from UI', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $response = $this->get('/admin/summary-reports')
        ->assertOk()
        ->assertDontSee('name="paper_size"', false)
        ->assertDontSee('Configure Document Signatories')
        ->assertDontSee('preview-toolbar')
        ->assertDontSee('btnZoomIn')
        ->assertSee('Barangay Secretary')
        ->assertDontSee('Reviewed by:')
        ->assertDontSee('Authorized Officer')
        ->assertSee('Punong Barangay');
});

it('strictly separates Administrative Summary Report and Monthly Incident Logbook templates', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Resident::create([
        'name' => 'Maria Santos',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'address' => 'Doyong',
        'status' => 'active',
        'zone' => 'zone1',
    ]);

    // Administrative Summary: Shows formal tables, does not show monthly logbook table
    $adminRes = $this->get('/admin/summary-reports?report_type=administrative')
        ->assertOk()
        ->assertSee('ADMINISTRATIVE SUMMARY REPORT')
        ->assertSee('SECTION A: RESIDENT RECORDS SUMMARY')
        ->assertSee('formal-admin-table')
        ->assertDontSee('formal-logbook-table');

    // Monthly Incident Report: Shows monthly logbook table, does not show Section A/B/C breakdown tables
    $monthlyRes = $this->get('/admin/summary-reports?report_type=monthly_incident&month=10&year=2026')
        ->assertOk()
        ->assertSee('BARANGAY EMERGENCY AND DISASTER INCIDENT REPORT')
        ->assertSee('formal-logbook-table')
        ->assertDontSee('SECTION A: RESIDENT RECORDS SUMMARY')
        ->assertDontSee('formal-admin-table');
});

it('renders actual records in formal tables when category is selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Resident::create([
        'name' => 'Arjo Reuel Gonzales',
        'first_name' => 'Arjo Reuel',
        'middle_name' => 'Latorre',
        'last_name' => 'Gonzales',
        'address' => 'Gonzales, Doyong',
        'sitio_purok' => 'Sitio Gonzales',
        'status' => 'active',
        'zone' => 'zone1',
    ]);

    \App\Models\Incident::create([
        'user_id' => $admin->id,
        'type' => 'flood',
        'location' => 'Sitio Central',
        'description' => 'Heavy rainfall caused ankle-deep flooding.',
        'status' => 'resolved',
        'logbook' => [
            'incident_date' => '2026-10-12',
            'location' => 'Sitio Central',
            'severity' => 'Moderate',
        ],
    ]);

    \App\Models\Alert::create([
        'created_by' => $admin->id,
        'emergency_type' => 'Medical Assistance',
        'location' => 'Sitio Paldit',
        'level' => 'high',
        'title' => 'Emergency Medical Response',
        'message' => 'Ambulance dispatched to patient',
        'is_active' => false,
        'verification_status' => 'Resolved',
    ]);

    // 1. Residents category
    $resReport = $this->get('/admin/summary-reports?category=residents')
        ->assertOk()
        ->assertSee('RESIDENT RECORDS REPORT')
        ->assertSee('Arjo Reuel')
        ->assertSee('Latorre')
        ->assertSee('Gonzales')
        ->assertSee('TOTAL REGISTERED RESIDENTS')
        ->assertSee('formal-gov-records-table')
        ->assertDontSee('DISASTER RECORDS REPORT')
        ->assertDontSee('EMERGENCY RECORDS REPORT');

    // 2. Disasters category
    $disReport = $this->get('/admin/summary-reports?category=disasters')
        ->assertOk()
        ->assertSee('DISASTER RECORDS REPORT')
        ->assertSee('Sitio Central')
        ->assertSee('Flood')
        ->assertSee('TOTAL DISASTER RECORDS')
        ->assertSee('formal-gov-records-table')
        ->assertDontSee('Arjo Reuel');

    // 3. Emergencies category
    $emgReport = $this->get('/admin/summary-reports?category=emergencies')
        ->assertOk()
        ->assertSee('EMERGENCY RECORDS REPORT')
        ->assertSee('Medical Assistance')
        ->assertSee('Sitio Paldit')
        ->assertSee('TOTAL EMERGENCY RECORDS')
        ->assertSee('formal-gov-records-table')
        ->assertDontSee('Arjo Reuel');
});

it('removes report type and applies record category with selected month and year', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Create resident in November 2026
    $resNov = Resident::create([
        'name' => 'Juan Dela Cruz',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'address' => 'Doyong',
        'status' => 'active',
        'zone' => 'zone2',
    ]);
    $resNov->created_at = \Carbon\Carbon::create(2026, 11, 15, 10, 0, 0, 'Asia/Manila');
    $resNov->save();

    // Create resident in December 2026
    $resDec = Resident::create([
        'name' => 'Pedro Penduko',
        'first_name' => 'Pedro',
        'last_name' => 'Penduko',
        'address' => 'Doyong',
        'status' => 'active',
        'zone' => 'zone3',
    ]);
    $resDec->created_at = \Carbon\Carbon::create(2026, 12, 5, 10, 0, 0, 'Asia/Manila');
    $resDec->save();

    // 1. Verify UI has category, month, and year, and does NOT have report_type
    $responseUI = $this->get('/admin/summary-reports')
        ->assertOk()
        ->assertDontSee('name="report_type"', false)
        ->assertDontSee('Report Type</label>', false)
        ->assertSee('name="category"', false)
        ->assertSee('name="month"', false)
        ->assertSee('name="year"', false)
        ->assertSee('Record Category');

    // 2. Query category=residents with month=11 and year=2026
    $novReport = $this->get('/admin/summary-reports?category=residents&month=11&year=2026')
        ->assertOk()
        ->assertSee('RESIDENT RECORDS REPORT')
        ->assertSee('FOR THE MONTH OF NOVEMBER, 2026')
        ->assertSeeText('Reporting Period: November 1–30, 2026')
        ->assertSee('Juan')
        ->assertSee('Dela Cruz')
        ->assertDontSee('Pedro Penduko');

    // 3. Query category=residents with month=12 and year=2026
    $decReport = $this->get('/admin/summary-reports?category=residents&month=12&year=2026')
        ->assertOk()
        ->assertSee('RESIDENT RECORDS REPORT')
        ->assertSee('FOR THE MONTH OF DECEMBER, 2026')
        ->assertSeeText('Reporting Period: December 1–31, 2026')
        ->assertSee('Pedro')
        ->assertSee('Penduko')
        ->assertDontSee('Juan Dela Cruz');
});


it('renders formal summary tables without changing the recorded totals', function () {
    $this->actingAs(User::factory()->create(['role'=>'admin']));
    foreach (['Single','Married','Widowed'] as $status) {
        Resident::create(['name'=>'Existing resident','address'=>'Doyong','civil_status'=>$status,'status'=>'active','zone'=>'zone1']);
    }
    $response = $this->get('/admin/summary-reports?category=all&month=10&year=2026');
    $response->assertOk()->assertSee('formal-admin-table')->assertSee('rawSignatureContainer');
    $response->assertViewHas('sections', fn ($sections) => $sections[0]['total'] === 3);
    if ($dir = getenv('UI_FIXTURE_DIR')) {
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir.'/summary-report.html', $response->getContent());
    }
});
