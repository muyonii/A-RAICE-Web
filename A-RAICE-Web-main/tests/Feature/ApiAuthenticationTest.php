<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;

test('mobile users can log in and receive a plain text token for every role', function () {
    foreach (UserRole::cases() as $role) {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', $user->name)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', $role->value);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
    }
});

test('mobile login returns a JSON error for a wrong password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertUnauthorized()
        ->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
});

test('mobile login is rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/login', [
            'email' => 'missing@example.com',
            'password' => 'incorrect-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/login', [
        'email' => 'missing@example.com',
        'password' => 'incorrect-password',
    ])->assertTooManyRequests();
});

test('mobile logout deletes only the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile');
    $otherToken = $user->createToken('other-device');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/logout')
        ->assertOk();

    expect($token->accessToken->fresh())->toBeNull();
    expect($otherToken->accessToken->fresh())->not->toBeNull();

    $this->app['auth']->forgetGuards();

    $this->withToken($token->plainTextToken)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('mobile user and logout endpoints reject unauthenticated requests', function () {
    $this->getJson('/api/user')->assertUnauthorized();
    $this->postJson('/api/logout')->assertUnauthorized();
});

test('mobile user response includes role and farmer profile only for farmers', function () {
    foreach (UserRole::cases() as $role) {
        $user = User::factory()->create(['role' => $role]);
        $profile = null;

        if ($role === UserRole::Farmer) {
            $profile = FarmerProfile::factory()->for($user)->create([
                'municipality' => MunicipalityEnum::Orion,
                'barangay' => 'San Vicente',
                'farm_lat' => 14.6234567,
                'farm_long' => 120.4567891,
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('role', $role->value);

        if ($profile !== null) {
            $response->assertJsonPath('farmer_profile.municipality', MunicipalityEnum::Orion->value)
                ->assertJsonPath('farmer_profile.barangay', 'San Vicente')
                ->assertJsonPath('farmer_profile.farm_lat', 14.6234567)
                ->assertJsonPath('farmer_profile.farm_long', 120.4567891);
        } else {
            $response->assertJsonMissingPath('farmer_profile');
        }
    }
});
