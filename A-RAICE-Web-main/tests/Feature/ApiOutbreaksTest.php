<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\User;

test('farmers are forbidden from viewing or closing outbreaks via the API', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $disease = Disease::factory()->create();
    $outbreak = Outbreak::create([
        'disease_id' => $disease->id,
        'municipality' => MunicipalityEnum::Balanga,
        'status' => OutbreakStatusEnum::Active,
    ]);

    $token = $farmer->createToken('mobile')->plainTextToken;

    $this->withToken($token)->getJson('/api/outbreaks')->assertForbidden();
    $this->withToken($token)->getJson("/api/outbreaks/{$outbreak->id}")->assertForbidden();
    $this->withToken($token)->patchJson("/api/outbreaks/{$outbreak->id}/close")->assertForbidden();
});

test('admin and LGU staff can list outbreaks and filter them', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease1 = Disease::factory()->create(['name' => 'Disease One']);
    $disease2 = Disease::factory()->create(['name' => 'Disease Two']);

    $outbreak1 = Outbreak::create([
        'disease_id' => $disease1->id,
        'municipality' => MunicipalityEnum::Balanga,
        'status' => OutbreakStatusEnum::Active,
    ]);

    $outbreak2 = Outbreak::create([
        'disease_id' => $disease2->id,
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Resolved,
    ]);

    $token = $admin->createToken('mobile')->plainTextToken;

    $listResponse = $this->withToken($token)->getJson('/api/outbreaks');

    $listResponse->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'disease' => ['id', 'name'],
                    'municipality',
                    'status',
                    'scans_count',
                    'created_at',
                ],
            ],
        ]);

    $filterResponse = $this->withToken($token)->getJson('/api/outbreaks?status=active');
    $filterResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $outbreak1->id);

    $filterMuni = $this->withToken($token)->getJson('/api/outbreaks?municipality=orion');
    $filterMuni->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $outbreak2->id);
});

test('admin and LGU staff can resolve an active outbreak', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $outbreak = Outbreak::create([
        'disease_id' => $disease->id,
        'municipality' => MunicipalityEnum::Hermosa,
        'status' => OutbreakStatusEnum::Active,
    ]);

    $token = $lgu->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/outbreaks/{$outbreak->id}/close");

    $response->assertOk()
        ->assertJsonPath('message', 'Outbreak resolved successfully.')
        ->assertJsonPath('outbreak.status', OutbreakStatusEnum::Resolved->value)
        ->assertJsonPath('outbreak.closed_by', $lgu->name);

    $outbreak->refresh();
    expect($outbreak->status)->toBe(OutbreakStatusEnum::Resolved)
        ->and($outbreak->closed_by)->toBe($lgu->id)
        ->and($outbreak->closed_at)->not->toBeNull();

    // Closing already resolved outbreak returns 409 Conflict
    $this->withToken($token)
        ->patchJson("/api/outbreaks/{$outbreak->id}/close")
        ->assertStatus(409);
});
