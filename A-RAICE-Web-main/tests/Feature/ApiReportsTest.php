<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\Report;
use App\Models\User;

test('farmers are forbidden from viewing or creating reports via the API', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $report = Report::create([
        'generated_by' => $admin->id,
        'municipality' => MunicipalityEnum::Balanga,
        'range_start' => '2026-01-01',
        'range_end' => '2026-01-31',
        'summary_data' => ['total_scans' => 0],
    ]);

    $token = $farmer->createToken('mobile')->plainTextToken;

    $this->withToken($token)->getJson('/api/reports')->assertForbidden();
    $this->withToken($token)->getJson("/api/reports/{$report->id}")->assertForbidden();
    $this->withToken($token)->postJson('/api/reports', [
        'municipality' => MunicipalityEnum::Balanga->value,
        'range_start' => '2026-01-01',
        'range_end' => '2026-01-31',
    ])->assertForbidden();
});

test('admin and LGU staff can list, create, and view reports via the API', function () {
    foreach ([UserRole::Admin, UserRole::LguStaff] as $role) {
        $this->app['auth']->forgetGuards();
        $user = User::factory()->create(['role' => $role]);
        $disease = Disease::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $createResponse = $this->withToken($token)->postJson('/api/reports', [
            'municipality' => MunicipalityEnum::Orion->value,
            'range_start' => '2026-01-01',
            'range_end' => '2026-01-31',
            'disease_id' => $disease->id,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('municipality', MunicipalityEnum::Orion->value)
            ->assertJsonPath('generated_by.id', $user->id)
            ->assertJsonStructure([
                'id',
                'summary_data',
                'generated_by' => ['id', 'name'],
            ]);

        $reportId = $createResponse->json('id');

        $this->withToken($token)->getJson('/api/reports')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'municipality', 'generated_by']]]);

        $this->withToken($token)->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonPath('id', $reportId)
            ->assertJsonPath('municipality', MunicipalityEnum::Orion->value);
    }
});

test('report creation validates date ranges and municipality', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $token = $admin->createToken('mobile')->plainTextToken;

    $this->withToken($token)->postJson('/api/reports', [
        'municipality' => 'invalid-municipality',
        'range_start' => '2026-02-01',
        'range_end' => '2026-01-01', // before range_start
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['municipality', 'range_end']);
});
