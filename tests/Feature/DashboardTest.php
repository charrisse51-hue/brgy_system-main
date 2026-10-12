<?php

use App\Models\Alert;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the three dashboard record totals', function (int $residents, int $incidents, int $alerts, string $role) {
    $admin = User::factory()->create(['role' => $role]);

    for ($index = 0; $index < $residents; $index++) {
        Resident::create([
            'name' => "Resident {$index}",
            'address' => 'Purok 1',
            'status' => $index % 2 === 0 ? 'active' : 'inactive',
        ]);
    }

    for ($index = 0; $index < $incidents; $index++) {
        Incident::create([
            'user_id' => $admin->id,
            'type' => 'flood',
            'location' => 'Purok 1',
            'status' => ['pending', 'ongoing', 'resolved'][$index % 3],
            'archived' => $index % 2 !== 0,
        ]);
    }

    for ($index = 0; $index < $alerts; $index++) {
        Alert::create([
            'title' => "Emergency record {$index}",
            'message' => 'Test emergency record',
            'level' => $index % 2 === 0 ? 'low' : 'high',
            'is_active' => $index % 2 === 0,
            'created_by' => $admin->id,
        ]);
    }

    $response = $this->actingAs($admin)->get($role === 'staff' ? '/staff/staffdashboard' : '/admin/dashboard');

    $response->assertOk()
        ->assertViewHas('totalResidents', $residents)
        ->assertViewHas('totalAlerts', $alerts)
        ->assertViewHas('totalRecords', $residents + $incidents + $alerts)
        ->assertSee('Records by Category')->assertSee('Monthly Records Trend')
        ->assertDontSee('HIGH ALERT ACTIVE');

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $headings = $xpath->query('//h3');

    expect($headings->length)->toBe(3);

    $expectedCards = [
        'Total Residents' => $residents,
        'Total Emergency Records' => $alerts,
        'All Records' => $residents + $incidents + $alerts,
    ];

    foreach ($expectedCards as $label => $count) {
        $value = $xpath->evaluate("string(//h3[normalize-space(.)='{$label}']/following-sibling::p[1])");
        expect(trim($value))->toBe(number_format($count));
    }
})->with([
    'empty admin records' => [0, 0, 0, 'admin'],
    'mixed admin records and statuses' => [2, 3, 4, 'admin'],
    'empty staff records' => [0, 0, 0, 'staff'],
    'mixed staff records and statuses' => [2, 3, 4, 'staff'],
]);

it('links staff alert panels to staff emergency records', function () {
    $staff = User::factory()->create(['role' => 'staff']);
    Alert::create([
        'title' => 'Active flood advisory', 'message' => 'Flood warning',
        'level' => 'high', 'is_active' => true, 'created_by' => $staff->id,
    ]);
    $this->actingAs($staff)->get('/staff/staffdashboard')->assertOk()
        ->assertSee('Active flood advisory')
        ->assertSee('href="'.route('staff.emergency.index').'"', false)
        ->assertDontSee('href="'.url('/admin/'), false)
        ->assertDontSee('System Settings')->assertDontSee('User Management')
        ->assertViewHas('monthlyRecords', fn ($months) => count($months) === 6 && array_sum(array_column($months, 'count')) === 1);
});
