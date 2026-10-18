<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\User;

test('a new farmer can register via the mobile API with valid Bataan coordinates', function () {
    $payload = [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@farmer.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'barangay' => 'Tuyo',
        'municipality' => MunicipalityEnum::Balanga->value,
        'contact_number' => '09171234567',
        'farm_lat' => 14.6760,
        'farm_long' => 120.5360,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'farmer_profile' => [
                    'municipality',
                    'barangay',
                    'farm_lat',
                    'farm_long',
                    'contact_number',
                ],
            ],
        ])
        ->assertJsonPath('user.name', 'Juan Dela Cruz')
        ->assertJsonPath('user.email', 'juan@farmer.test')
        ->assertJsonPath('user.role', UserRole::Farmer->value)
        ->assertJsonPath('user.farmer_profile.municipality', MunicipalityEnum::Balanga->value)
        ->assertJsonPath('user.farmer_profile.barangay', 'Tuyo')
        ->assertJsonPath('user.farmer_profile.contact_number', '09171234567');

    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    $user = User::where('email', 'juan@farmer.test')->firstOrFail();
    expect($user->role)->toBe(UserRole::Farmer)
        ->and($user->farmerProfile)->not->toBeNull()
        ->and($user->farmerProfile->municipality)->toBe(MunicipalityEnum::Balanga);
});

test('mobile registration rejects farm coordinates not inside the selected municipality', function () {
    // Orion coordinates supplied for Balanga municipality
    $payload = [
        'name' => 'Wrong Location Farmer',
        'email' => 'wrong@farmer.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'barangay' => 'Wawa',
        'municipality' => MunicipalityEnum::Balanga->value,
        'contact_number' => '09179998888',
        'farm_lat' => 14.6200,
        'farm_long' => 120.5800,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['farm_lat']);

    expect(User::where('email', 'wrong@farmer.test')->exists())->toBeFalse();
});

test('mobile registration rejects coordinates completely outside Bataan', function () {
    $payload = [
        'name' => 'Out Of Bounds Farmer',
        'email' => 'oob@farmer.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'barangay' => 'Somewhere',
        'municipality' => MunicipalityEnum::Balanga->value,
        'contact_number' => '09179998888',
        'farm_lat' => 12.0000,
        'farm_long' => 125.0000,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['farm_lat', 'farm_long']);
});

test('mobile registration validates required fields and unique email', function () {
    User::factory()->create(['email' => 'existing@farmer.test']);

    $response = $this->postJson('/api/register', [
        'email' => 'existing@farmer.test',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'barangay',
            'municipality',
            'contact_number',
            'farm_lat',
            'farm_long',
        ]);
});
