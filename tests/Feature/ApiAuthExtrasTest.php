<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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
