<?php

use App\Models\User;
use App\Models\Incident;
use App\Models\Alert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders standardized sidebar branding across all admin pages', function (string $path) {
    $user = User::factory()->create(['role' => 'admin']);
    $user->resident()->create(['name' => 'Admin Resident', 'first_name' => 'Admin', 'last_name' => 'User', 'address' => 'Purok 1', 'status' => 'active']);
    Incident::create(['user_id' => $user->id, 'type' => 'flood', 'location' => 'Purok 1', 'status' => 'pending']);
    Alert::create(['title' => 'Advisory', 'message' => 'Record', 'level' => 'low', 'is_active' => true, 'created_by' => $user->id]);

    $response = $this->actingAs($user)->get($path)->assertOk();

    $content = $response->getContent();

    // Verify DOM structure with DOMXPath
    $document = new DOMDocument;
    @$document->loadHTML($content);
    $xpath = new DOMXPath($document);

    // 1. Exactly one sidebar brand container
    $brandNodes = $xpath->query('//aside[contains(@class, "sidebar")]//div[contains(concat(" ", normalize-space(@class), " "), " sidebar-brand ")]');
    expect($brandNodes->length)->toBe(1);

    // 2. Exactly one brand logo with standardized attributes
    $logoNodes = $xpath->query('//aside[contains(@class, "sidebar")]//div[contains(concat(" ", normalize-space(@class), " "), " sidebar-brand ")]//img[contains(@class, "sidebar-brand-logo")]');
    expect($logoNodes->length)->toBe(1);

    $logo = $logoNodes->item(0);
    expect($logo->getAttribute('width'))->toBe('44');
    expect($logo->getAttribute('height'))->toBe('44');
    expect($logo->getAttribute('src'))->toContain('barangay-doyong-logo.png');
    expect($logo->getAttribute('alt'))->toBe('Barangay Doyong logo');

    // 3. Exactly one brand title "Brgy. Doyong"
    $titleNodes = $xpath->query('//aside[contains(@class, "sidebar")]//div[contains(concat(" ", normalize-space(@class), " "), " sidebar-brand ")]//*[contains(@class, "sidebar-brand-title") or self::h1]');
    expect($titleNodes->length)->toBeGreaterThanOrEqual(1);
    expect(trim($titleNodes->item(0)->textContent))->toBe('Brgy. Doyong');

    // 4. Subtitle must be "RECORDS ADMIN"
    $subtitleNodes = $xpath->query('//aside[contains(@class, "sidebar")]//div[contains(concat(" ", normalize-space(@class), " "), " sidebar-brand ")]//*[contains(@class, "sidebar-brand-subtitle") or self::p]');
    expect($subtitleNodes->length)->toBeGreaterThanOrEqual(1);
    expect(trim($subtitleNodes->item(0)->textContent))->toBe('RECORDS ADMIN');

    // 5. Must not contain obsolete subtitles
    expect($content)->not->toContain('EDRMS ADMIN');
})->with([
    '/admin/dashboard',
    '/admin/incidents',
    '/admin/emergency',
    '/admin/residents',
    '/admin/users',
    '/admin/activity-logs',
    '/admin/summary-reports',
    '/admin/settings',
]);

