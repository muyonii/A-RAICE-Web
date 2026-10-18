<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;

test('farmers receive all municipality summaries and only their own municipality details', function () {
    $ownFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Balanga)
        ->create([
            'full_name' => 'Private Own Farmer',
            'contact_number' => '09111111111',
            'barangay' => 'Own Barangay',
        ]);
    $otherFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create([
            'full_name' => 'Private Other Farmer',
            'contact_number' => '09999999999',
        ]);
    $ownOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $otherOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
    ]);
    Alert::factory()->for($ownOutbreak)->create(['severity' => SeverityEnum::High]);
    Alert::factory()->for($otherOutbreak)->create(['severity' => SeverityEnum::Medium]);
    $ownScan = Scan::factory()->for($ownFarm, 'farmer')->create([
        'outbreak_id' => $ownOutbreak->id,
        'scan_date' => '2026-10-01 10:00:00',
    ]);
    Scan::factory()->for($otherFarm, 'farmer')->create([
        'outbreak_id' => $otherOutbreak->id,
        'scan_date' => '2026-10-02 10:00:00',
    ]);

    $response = $this->withToken($ownFarm->user->createToken('map')->plainTextToken)
        ->getJson('/api/map')
        ->assertOk()
        ->assertJsonCount(12, 'municipalities')
        ->assertJsonCount(1, 'outbreaks')
        ->assertJsonPath('outbreaks.0.id', $ownOutbreak->id)
        ->assertJsonPath('outbreaks.0.municipality', 'balanga')
        ->assertJsonCount(1, 'farms')
        ->assertJsonPath('farms.0.id', $ownFarm->id)
        ->assertJsonPath('farms.0.barangay', 'Own Barangay')
        ->assertJsonPath('farms.0.municipality', 'balanga')
        ->assertJsonPath('farms.0.scan.id', $ownScan->id)
        ->assertJsonMissingPath('farms.0.name')
        ->assertJsonMissingPath('farms.0.contact_number')
        ->assertJsonMissing(['Private Other Farmer', '09999999999']);

    $municipalities = collect($response->json('municipalities'))->keyBy('slug');

    expect($municipalities)->toHaveCount(12)
        ->and($municipalities->get('balanga'))->toBe(['slug' => 'balanga', 'severity' => 'high'])
        ->and($municipalities->get('orion'))->toBe(['slug' => 'orion', 'severity' => 'medium'])
        ->and($municipalities->get('abucay'))->toBe(['slug' => 'abucay', 'severity' => null]);

    foreach ($municipalities as $municipality) {
        expect(array_keys($municipality))->toBe(['slug', 'severity']);
    }
});

test('admins receive every farm with coordinates and LGUs receive only active outbreak farm pins', function () {
    $balangaOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $orionOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
    ]);
    Alert::factory()->for($balangaOutbreak)->create(['severity' => SeverityEnum::Low]);
    Alert::factory()->for($balangaOutbreak)->create(['severity' => SeverityEnum::High]);
    Alert::factory()->for($orionOutbreak)->create(['severity' => SeverityEnum::Medium]);

    $balangaFarm = FarmerProfile::factory()->create([
        'full_name' => 'Balanga Pin Farmer',
        'contact_number' => '09122222222',
        'barangay' => 'Balanga Barangay',
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $orionFarm = FarmerProfile::factory()->create([
        'full_name' => 'Orion Pin Farmer',
        'contact_number' => '09133333333',
        'barangay' => 'Orion Barangay',
        'municipality' => MunicipalityEnum::Orion,
    ]);
    $resolvedFarm = FarmerProfile::factory()->create();
    $unlinkedFarm = FarmerProfile::factory()->create();
    $noScansFarm = FarmerProfile::factory()->create();
    Scan::factory()->for($balangaFarm, 'farmer')->create([
        'outbreak_id' => $balangaOutbreak->id,
        'scan_date' => '2026-10-03 10:00:00',
    ]);
    $latestScan = Scan::factory()->for($balangaFarm, 'farmer')->create([
        'outbreak_id' => $balangaOutbreak->id,
        'scan_date' => '2026-10-04 10:00:00',
    ]);
    Scan::factory()->for($orionFarm, 'farmer')->create([
        'outbreak_id' => $orionOutbreak->id,
        'scan_date' => '2026-10-02 10:00:00',
    ]);
    $resolvedOutbreak = Outbreak::factory()->create([
        'status' => OutbreakStatusEnum::Resolved,
    ]);
    Scan::factory()->for($resolvedFarm, 'farmer')->create([
        'outbreak_id' => $resolvedOutbreak->id,
    ]);
    Scan::factory()->for($unlinkedFarm, 'farmer')->create();
    $latestAnyScan = Scan::factory()->for($balangaFarm, 'farmer')->create([
        'scan_date' => '2026-10-05 10:00:00',
    ]);

    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $lguResponse = $this->withToken($lgu->createToken('map')->plainTextToken)
        ->getJson('/api/map')
        ->assertOk()
        ->assertJsonCount(12, 'municipalities')
        ->assertJsonCount(2, 'outbreaks')
        ->assertJsonCount(2, 'farms');

    $lguFarms = collect($lguResponse->json('farms'))->keyBy('id');

    expect($lguFarms->keys()->all())->toEqualCanonicalizing([$balangaFarm->id, $orionFarm->id])
        ->and($lguFarms->get($balangaFarm->id)['scan']['id'])->toBe($latestScan->id)
        ->and($lguFarms->get($balangaFarm->id)['scan']['scan_date'])->toBe('2026-10-04')
        ->and($lguFarms->get($orionFarm->id)['scan']['scan_date'])->toBe('2026-10-02');

    $this->app['auth']->forgetGuards();
    $adminResponse = $this->withToken($admin->createToken('map')->plainTextToken)
        ->getJson('/api/map')
        ->assertOk()
        ->assertJsonCount(12, 'municipalities')
        ->assertJsonCount(2, 'outbreaks')
        ->assertJsonCount(5, 'farms');

    $municipalities = collect($adminResponse->json('municipalities'))->keyBy('slug');
    $adminFarms = collect($adminResponse->json('farms'))->keyBy('id');

    expect($municipalities->get('balanga'))->toBe(['slug' => 'balanga', 'severity' => 'high'])
        ->and($municipalities->get('orion'))->toBe(['slug' => 'orion', 'severity' => 'medium'])
        ->and(array_keys($municipalities->get('balanga')))->toBe(['slug', 'severity'])
        ->and($adminFarms->keys()->all())->toEqualCanonicalizing([
            $balangaFarm->id,
            $orionFarm->id,
            $resolvedFarm->id,
            $unlinkedFarm->id,
            $noScansFarm->id,
        ])
        ->and($adminFarms->get($balangaFarm->id)['name'])->toBe('Balanga Pin Farmer')
        ->and($adminFarms->get($balangaFarm->id)['contact_number'])->toBe('09122222222')
        ->and($adminFarms->get($balangaFarm->id)['barangay'])->toBe('Balanga Barangay')
        ->and($adminFarms->get($balangaFarm->id)['municipality'])->toBe('balanga')
        ->and($adminFarms->get($balangaFarm->id)['scan']['id'])->toBe($latestAnyScan->id)
        ->and($adminFarms->get($balangaFarm->id)['scan']['disease'])->toBe($latestAnyScan->disease->name)
        ->and($adminFarms->get($balangaFarm->id)['scan']['scan_date'])->toBe('2026-10-05')
        ->and($adminFarms->get($orionFarm->id)['scan']['scan_date'])->toBe('2026-10-02')
        ->and($adminFarms->get($noScansFarm->id)['scan'])->toBeNull();
});

test('map endpoint requires authentication', function () {
    $this->getJson('/api/map')->assertUnauthorized();
});
