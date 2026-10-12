<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the shared theme on authentication and record forms', function (string $role, string $path) {
    if ($role !== 'guest') {
        $this->actingAs(User::factory()->create(['role' => $role]));
    }
    $response = $this->followingRedirects()->get($path)->assertOk();
    if ($role === 'guest') $response->assertSee('#15803D')->assertSee('login-card');
    else $response->assertSee('id="ui-feedback"', false);
    if ($directory = getenv('UI_FIXTURE_DIR')) {
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        file_put_contents($directory.'/'.str_replace('/', '_', trim($path, '/')).'.html', $response->getContent());
    }
})->with([
    ['guest', '/login'],
    ['admin', '/admin/residents/create'],
    ['admin', '/admin/incidents/create'],
    ['admin', '/admin/emergency/create'],
    ['staff', '/staff/residents/create'],
    ['staff', '/staff/disaster/create'],
    ['staff', '/staff/emergency/create'],
    ['resident', '/resident/emergency/create'],
]);
