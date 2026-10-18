<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'barangay' => 'Test Barangay',
        'municipality' => 'orion',
        'contact_number' => '09123456789',
        'farm_lat' => 14.6216,
        'farm_long' => 120.5772,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    $this->assertDatabaseHas('farmer_profiles', ['contact_number' => '09123456789']);
    expect(User::where('email', 'test@example.com')->firstOrFail()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();

    $this->get('/dashboard')->assertOk();
});
