<?php

use App\Enums\MunicipalityEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\FarmerProfile;
use App\Models\User;

test('farmers cannot broadcast manual alerts', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $token = $farmer->createToken('mobile')->plainTextToken;

    $this->withToken($token)->postJson('/api/alerts/broadcast', [
        'message' => 'Farmers unauthorized broadcast',
    ])->assertForbidden();
});

test('admin and LGU staff can broadcast alerts to a specific municipality or all farmers', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $farmerBalanga = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Balanga]);
    $farmerOrion = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Orion]);

    $token = $admin->createToken('mobile')->plainTextToken;

    // Broadcast to Balanga only
    $response = $this->withToken($token)->postJson('/api/alerts/broadcast', [
        'message' => 'Typhoon alert for Balanga rice farms',
        'severity' => SeverityEnum::High->value,
        'municipality' => MunicipalityEnum::Balanga->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'Broadcast alert sent successfully.')
        ->assertJsonPath('alert.severity', SeverityEnum::High->value)
        ->assertJsonPath('alert.municipality', MunicipalityEnum::Balanga->value);

    $alertId = $response->json('alert.id');

    // Stored municipality on alert model
    $alert = Alert::findOrFail($alertId);
    expect($alert->municipality)->toBe(MunicipalityEnum::Balanga->value);

    // Balanga farmer received the alert
    expect($farmerBalanga->user->alerts()->where('alerts.id', $alertId)->exists())->toBeTrue();
    // Orion farmer did not receive it
    expect($farmerOrion->user->alerts()->where('alerts.id', $alertId)->exists())->toBeFalse();
});

test('a broadcast with a municipality returns it from GET /api/alerts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Balanga])->user;

    $adminToken = $admin->createToken('mobile')->plainTextToken;
    $farmerToken = $farmer->createToken('mobile')->plainTextToken;

    $broadcastResponse = $this->withToken($adminToken)->postJson('/api/alerts/broadcast', [
        'message' => 'Infestation warning for Balanga',
        'severity' => SeverityEnum::High->value,
        'municipality' => MunicipalityEnum::Balanga->value,
    ])->assertCreated();

    $alertId = $broadcastResponse->json('alert.id');

    $alertsResponse = $this->withToken($farmerToken)->getJson('/api/alerts')->assertOk();

    $alertsResponse->assertJsonPath('data.0.id', $alertId)
        ->assertJsonPath('data.0.municipality', MunicipalityEnum::Balanga->value);
});
