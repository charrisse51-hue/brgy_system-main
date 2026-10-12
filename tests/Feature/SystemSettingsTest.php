<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for system settings', function () {
    $this->get('/admin/settings')->assertRedirect('/login');
});

it('shows system settings and its sidebar link to administrators', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/admin/settings')->assertOk()
        ->assertSee('System Settings')->assertSee('General Configuration')
        ->assertSee(config('app.timezone'))
        ->assertSee(route('admin.settings.index'))
        ->assertSee(route('admin.users.index'));
});

it('denies system settings access to other roles', function ($role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get('/admin/settings')->assertForbidden();
})->with(['staff', 'resident']);

it('keeps the system settings link on pages with separate sidebars', function ($path) {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get($path)->assertOk()
        ->assertSee('System Settings')
        ->assertSee('href="'.route('admin.settings.index').'"', false);
})->with(['/admin/dashboard', '/admin/users']);
