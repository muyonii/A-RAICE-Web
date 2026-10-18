<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('firebase login swaps registered email for web Sanctum token', function () {
    $user = User::factory()->create([
        'email' => 'firebase.farmer@test.local',
        'role' => UserRole::Farmer,
    ]);

    FarmerProfile::factory()->for($user)->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);

    $response = $this->postJson('/api/auth/firebase', [
        'email' => 'firebase.farmer@test.local',
        'firebase_uid' => 'sample-uid-12345',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email', 'farmer_profile']])
        ->assertJsonPath('user.email', 'firebase.farmer@test.local');

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('firebase login returns 404 when email is not registered on web system', function () {
    $response = $this->postJson('/api/auth/firebase', [
        'email' => 'unregistered@test.local',
    ]);

    $response->assertNotFound()
        ->assertJsonPath('message', 'No account found with this email on the web system. Please register first.');
});

test('forgot password endpoint requests reset link via email', function () {
    $user = User::factory()->create(['email' => 'reset@test.local']);

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'reset@test.local',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message']);
});

test('reset password endpoint updates user password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'resetme@test.local',
        'password' => Hash::make('OldPass123!'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => 'resetme@test.local',
        'password' => 'NewBrandPass123!',
        'password_confirmation' => 'NewBrandPass123!',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message']);

    $user->refresh();
    expect(Hash::check('NewBrandPass123!', $user->password))->toBeTrue();
});
