<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for activity logs', function () {
    $this->get('/admin/activity-logs')->assertRedirect('/login');
});

it('renders the empty activity log page', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/activity-logs')->assertOk()->assertSee('No activity logs yet');
});

it('shows recorded actions newest first and paginates older entries', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    for ($i = 1; $i <= 21; $i++) {
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => sprintf('Recorded action %02d', $i),
            'description' => 'Updated incident details',
            'model_type' => 'Incident',
            'model_id' => 42,
        ]);
    }

    $this->actingAs($admin)->get('/admin/activity-logs')->assertOk()
        ->assertSee($admin->name)->assertSee('Updated incident details')
        ->assertSee('Incident #42')->assertSeeInOrder(['Recorded action 21', 'Recorded action 20'])
        ->assertDontSee('Recorded action 01')->assertSee('Next');
    $this->get('/admin/activity-logs?page=2')->assertOk()
        ->assertSee('Recorded action 01')->assertDontSee('Recorded action 21');

    $this->get('/admin/activity-logs?per_page=5')->assertOk()
        ->assertSee('Showing 1 to 5 of 21 actions')
        ->assertSee('Recorded action 17')->assertDontSee('Recorded action 16')
        ->assertSee('per_page=5&amp;page=2', false)
        ->assertSee('aria-current="page"', false);
    $this->get('/admin/activity-logs?per_page=5&page=2')->assertOk()
        ->assertSee('Showing 6 to 10 of 21 actions')
        ->assertSee('Recorded action 16')->assertDontSee('Recorded action 17');
    $this->get('/admin/activity-logs?per_page=999')->assertOk()
        ->assertSee('Showing 1 to 20 of 21 actions');
});

it('escapes recorded text in the activity log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => '<script>alert(1)</script>',
    ]);
    $this->actingAs($admin)->get('/admin/activity-logs')->assertOk()
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false);
});
