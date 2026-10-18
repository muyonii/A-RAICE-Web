<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('authenticated farmer can update their profile via PATCH /api/user', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'orig@farmer.test',
        'role' => UserRole::Farmer,
    ]);

    $profile = FarmerProfile::factory()->for($user)->create([
        'full_name' => 'Original Name',
        'barangay' => 'Old Barangay',
        'municipality' => MunicipalityEnum::Balanga,
        'contact_number' => '09170000000',
        'farm_lat' => 14.6760,
        'farm_long' => 120.5360,
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson('/api/user', [
            'name' => 'Updated Name',
            'barangay' => 'New Barangay',
            'contact_number' => '09178888888',
        ]);

    $response->assertOk()
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('farmer_profile.barangay', 'New Barangay')
        ->assertJsonPath('farmer_profile.contact_number', '09178888888');

    $user->refresh();
    $profile->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($profile->full_name)->toBe('Updated Name')
        ->and($profile->barangay)->toBe('New Barangay')
        ->and($profile->contact_number)->toBe('09178888888');
});

test('profile update validates municipality and farm coordinates if changed', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($user)->create([
        'municipality' => MunicipalityEnum::Balanga,
        'farm_lat' => 14.6760,
        'farm_long' => 120.5360,
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    // Moving to coordinates outside Balanga
    $response = $this->withToken($token)
        ->patchJson('/api/user', [
            'farm_lat' => 14.6200,
            'farm_long' => 120.5800,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['farm_lat']);
});

test('authenticated user can change password via PUT /api/user/password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Password updated successfully.');

    $user->refresh();
    expect(Hash::check('NewPassword456!', $user->password))->toBeTrue();
});

test('password change rejects incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});
