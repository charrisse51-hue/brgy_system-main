<?php

use App\Models\User;
use App\Models\Resident;
use App\Models\Incident;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the improved dashboard with reference-inspired features', function () {
    $admin = User::factory()->create(['name' => 'Ian Administrator', 'role' => 'admin']);

    // Seed test residents
    for ($i = 1; $i <= 6; $i++) {
        Resident::create([
            'name' => "Resident {$i}",
            'first_name' => "Resident",
            'last_name' => "Number {$i}",
            'address' => "Purok {$i}, Barangay Doyong",
            'sitio_purok' => "Purok {$i}",
            'status' => 'active',
        ]);
    }

    // Seed test incidents and alerts
    Incident::create([
        'user_id' => $admin->id,
        'type' => 'flood',
        'location' => 'Purok 1',
        'status' => 'pending',
    ]);
    Alert::create([
        'title' => 'Flood Warning Advisory',
        'message' => 'Heavy rainfall alert',
        'level' => 'high',
        'is_active' => true,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

    // 1. Header checks
    $response->assertSee('DASHBOARD');
    $response->assertSee('WELCOME,');
    $response->assertSee('IAN ADMINISTRATOR');
    $response->assertSee('dashboardRealtimeClock', false);
    $response->assertSee('dashboardRealtimeDate', false);

    // 2. Verified removal of Information Cards (About System, About Barangay, Your Account)
    $response->assertDontSee('About the System');
    $response->assertDontSee('SYSTEM MODULE');
    $response->assertDontSee('About the Barangay');
    $response->assertDontSee('TOTAL REGISTERED CITIZENS');
    $response->assertDontSee('Your Account');

    // 3. Four Statistics Cards checks
    $response->assertSee('Total Residents');
    $response->assertSee('Total Emergency Records');
    $response->assertSee('Total Disaster Records');
    $response->assertSee('All Records');

    // 4. Recently Added Residents Table checks
    $response->assertSee('Recently Added Residents');
    $response->assertSee('Resident ID');
    $response->assertSee('Full Name');
    $response->assertSee('Address / Purok');
    $response->assertSee('Date Added');
    $response->assertSee('#RES-');

    // Verify exactly 5 residents in the recent table (since 6 were created and take(5) is used)
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $residentRows = $xpath->query('//table[contains(@class, "data-table")]//tbody//tr');
    expect($residentRows->length)->toBe(5);

    // 5. Preserved Features checks: charts, activity
    $response->assertSee('Records by Category');
    $response->assertSee('Monthly Records Trend');
    $response->assertSee('Recent activity');
    $response->assertDontSee('Quick actions');
    $response->assertDontSee('EDRMS Status');
    $response->assertSee('SYSTEM UPDATE');
});

