<?php

use App\Enums\MunicipalityEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\DeviceToken;
use App\Models\FarmerProfile;
use App\Models\User;

test('authenticated user can register and unregister device tokens for push notifications', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $regResponse = $this->withToken($token)->postJson('/api/devices', [
        'token' => 'fcm-device-token-12345',
        'platform' => 'android',
    ]);

    $regResponse->assertCreated()
        ->assertJsonPath('message', 'Device token registered successfully.');

    expect(DeviceToken::where('user_id', $user->id)->where('token', 'fcm-device-token-12345')->exists())->toBeTrue();

    $delResponse = $this->withToken($token)->deleteJson('/api/devices', [
        'token' => 'fcm-device-token-12345',
    ]);

    $delResponse->assertOk()
        ->assertJsonPath('message', 'Device token unregistered successfully.');

    expect(DeviceToken::where('token', 'fcm-device-token-12345')->exists())->toBeFalse();
});

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

    // Balanga farmer received the alert
    expect($farmerBalanga->user->alerts()->where('alerts.id', $alertId)->exists())->toBeTrue();
    // Orion farmer did not receive it
    expect($farmerOrion->user->alerts()->where('alerts.id', $alertId)->exists())->toBeFalse();
});
