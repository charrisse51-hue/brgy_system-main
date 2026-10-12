<?php

use App\Models\User;
use App\Models\Incident;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders one stable sidebar without a toggle across workspaces', function (string $role, string $path) {
    $user = User::factory()->create(['role' => $role]);
    if ($role === 'resident') {
        $user->resident()->create(['name' => $user->name, 'first_name' => 'Test', 'last_name' => 'Resident', 'address' => 'Purok 1', 'status' => 'active']);
    }
    if (str_ends_with($path, 'dashboard') && $role !== 'resident') {
        $user->resident()->create(['name' => 'Preview Resident', 'address' => 'Purok 1', 'status' => 'active']);
        Incident::create(['user_id' => $user->id, 'type' => 'flood', 'location' => 'Purok 1', 'status' => 'pending']);
        Alert::create(['title' => 'Preview advisory', 'message' => 'Preview record', 'level' => 'low', 'is_active' => true, 'created_by' => $user->id]);
    }
    if ($role !== 'resident' && !str_ends_with($path, 'dashboard')) {
        $location = 'Purok 7, Riverside evacuation access road near the community health center, Barangay Doyong, Calasiao, Pangasinan - ' . str_repeat('LongLocationWithoutSpaces', 5);
        $user->resident()->create(['name' => 'Long Name Preview Resident', 'first_name' => 'Long Name Preview', 'last_name' => 'Resident', 'address' => $location, 'contact_number' => '09123456789', 'status' => 'active']);
        Incident::create(['user_id' => $user->id, 'type' => 'flood', 'location' => $location, 'status' => 'pending', 'description' => str_repeat('Floodwater affecting residential access roads. ', 10), 'logbook' => ['complainant_name' => 'Long Name Reporting Resident', 'recorded_by' => $user->name]]);
        Alert::create(['title' => 'Flood rescue response', 'message' => str_repeat('Residents assisted at the evacuation center. ', 10), 'level' => 'high', 'is_active' => true, 'created_by' => $user->id]);
        \App\Models\ActivityLog::create(['user_id' => $user->id, 'action' => 'created', 'description' => $location, 'model_type' => Incident::class, 'model_id' => 1]);
    }
    $response = $this->actingAs($user)->get($path)->assertOk();

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " ui-mobile-menu ")]')->length)->toBe(0);
    expect($xpath->query('//aside')->length)->toBe(1);
    $response->assertDontSee('ui-nav-toggle');
    foreach ($xpath->query('//aside//nav//a[@href]') as $link) {
        $this->get($link->getAttribute('href'))->assertOk();
    }

    // Optional static fixtures for browser layout tests; never expose a test route.
    if ($directory = getenv('UI_FIXTURE_DIR')) {
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        file_put_contents($directory.'/'.str_replace('/', '_', trim($path, '/')).'.html', $response->getContent());
    }
})->with([
    ['admin', '/admin/dashboard'],
    ['admin', '/admin/residents'],
    ['admin', '/admin/emergency'],
    ['admin', '/admin/incidents'],
    ['admin', '/admin/activity-logs'],
    ['admin', '/admin/summary-reports'],
    ['admin', '/admin/settings'],
    ['admin', '/admin/users'],
    ['staff', '/staff/staffdashboard'],
    ['staff', '/staff/disaster'],
    ['staff', '/staff/emergency'],
    ['staff', '/staff/residents'],
    ['staff', '/staff/summary-reports'],
    ['resident', '/resident/dashboard'],
    ['resident', '/resident/profile'],
    ['resident', '/resident/alerts'],
]);
