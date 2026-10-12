<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the modern redesigned login interface with official branding', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertDontSee('EDMS-Doyong')
        ->assertSee('BARANGAY DOYONG')
        ->assertSee('Emergency, Disaster &amp; Records Management System', false)
        ->assertDontSee('Secure access for authorized barangay personnel.')
        ->assertSee('Barangay Doyong, Calasiao, Pangasinan')
        ->assertDontSee('Authorized Personnel Access Only')
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('Forgot Password?')
        ->assertSee('Log In')
        ->assertSee('barangay-doyong-logo.png')
        ->assertSee('barangay-hall-background.jpg')
        ->assertSee('Remember my username')
        ->assertSee('autocomplete="current-password"', false);
});

it('authenticates valid administrator credentials and redirects to admin dashboard', function () {
    $user = User::factory()->create([
        'username' => 'admin_test',
        'email' => 'admin_test@example.com',
        'password' => bcrypt('AdminPassword123!'),
        'role' => 'admin',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'admin_test',
        'password' => 'AdminPassword123!',
    ]);

    $response->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('authenticates valid staff credentials and redirects to staff dashboard', function () {
    $user = User::factory()->create([
        'username' => 'staff_test',
        'email' => 'staff_test@example.com',
        'password' => bcrypt('StaffPassword123!'),
        'role' => 'staff',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'staff_test@example.com',
        'password' => 'StaffPassword123!',
    ]);

    $response->assertRedirect('/staff/staffdashboard');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials with validation errors', function () {
    $response = $this->from('/login')->post('/login', [
        'email' => 'nonexistent_user',
        'password' => 'WrongPassword!',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});


it('validates missing and malformed login input without authenticating', function () {
    $this->from('/login')->post('/login', ['email'=>'','password'=>''])
        ->assertRedirect('/login')->assertSessionHasErrors(['email'=>'Enter your username or email.','password'=>'Enter your password.']);
    $this->assertGuest();
    $this->post('/login', ['email'=>str_repeat('a',256),'password'=>['invalid']])
        ->assertSessionHasErrors(['email','password']);
    $this->assertGuest();
});
