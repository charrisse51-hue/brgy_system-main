<?php

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires login to browse archives', function () {
    $this->get('/admin/archives')->assertRedirect('/login');
});

it('renders the empty archives page', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/archives')->assertOk()->assertSee('No archived reports found');
});

it('moves resolved incidents from active records into archives', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $incident = Incident::create([
        'user_id' => $admin->id, 'type' => 'flood',
        'location' => 'Archived location example', 'status' => 'resolved',
    ]);
    $this->actingAs($admin)->from('/admin/incidents')
        ->patch("/admin/incidents/{$incident->id}/archive")
        ->assertRedirect('/admin/incidents')->assertSessionHas('success');

    expect($incident->fresh()->archived)->toBeTrue();
    $this->get('/admin/archives')->assertOk()->assertSee($incident->location)
        ->assertDontSee('title="Archive"', false)
        ->assertDontSee('title="Update Status"', false);
    $this->get('/admin/incidents')->assertOk()->assertDontSee($incident->location)
        ->assertViewHas('totalCount', 0);
});

it('keeps unresolved incidents out of archives', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $incident = Incident::create([
        'user_id' => $admin->id, 'type' => 'fire',
        'location' => 'Active location example', 'status' => 'pending',
    ]);
    $this->actingAs($admin)->from('/admin/incidents')
        ->patch("/admin/incidents/{$incident->id}/archive")
        ->assertSessionHas('error');
    expect($incident->fresh()->archived)->toBeFalse();
    $this->get('/admin/archives')->assertOk()->assertDontSee($incident->location);
});
