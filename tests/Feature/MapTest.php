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
use Inertia\Testing\AssertableInertia as Assert;

test('the map shows the highest alert severity for each outbreak', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $outbreakWithAlerts = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
    ]);
    $outbreakWithoutAlerts = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orani,
    ]);

    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::Low]);
    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::High]);
    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::Medium]);

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 2)
            ->where('outbreaks.0.id', $outbreakWithAlerts->id)
            ->where('outbreaks.0.severity', 'high')
            ->where('outbreaks.1.id', $outbreakWithoutAlerts->id)
            ->where('outbreaks.1.severity', 'low')
            ->has('municipalityStatuses', 12)
            ->where('municipalityStatuses.9.severity', 'high')
            ->where('municipalityStatuses.8.severity', 'low')
            ->where('ownMunicipality', null)
            ->has('farms', 0));
});

test('the map identifies the signed-in farmer municipality', function () {
    $profile = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create(['farm_lat' => 14.52, 'farm_long' => 120.48]);

    $this->actingAs($profile->user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->where('ownMunicipality', 'orion')
            ->has('farms', 1)
            ->where('farms.0.id', $profile->id)
            ->where('farms.0.barangay', $profile->barangay)
            ->where('farms.0.municipality', 'orion')
            ->where('farms.0.lat', 14.52)
            ->where('farms.0.lng', 120.48)
            ->where('farms.0.scan', null));
});

test('a farmer map pin contains only their farm and latest scan data', function () {
    $ownFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create([
            'full_name' => 'Own Farmer Name',
            'contact_number' => '09111111111',
            'barangay' => 'Own Barangay',
        ]);
    $otherFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orani)
        ->create([
            'full_name' => 'Private Other Farmer Name',
            'contact_number' => '09999999999',
        ]);
    Scan::factory()->for($ownFarm, 'farmer')->create([
        'scan_date' => '2026-09-30 10:00:00',
    ]);
    $ownScan = Scan::factory()->for($ownFarm, 'farmer')->create([
        'scan_date' => '2026-10-01 10:00:00',
    ]);
    $otherScan = Scan::factory()->for($otherFarm, 'farmer')->create([
        'scan_date' => '2026-10-02 10:00:00',
    ]);

    $response = $this->actingAs($ownFarm->user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('farms', 1)
            ->where('farms.0.id', $ownFarm->id)
            ->where('farms.0.barangay', 'Own Barangay')
            ->where('farms.0.municipality', 'orion')
            ->where('farms.0.scan.id', $ownScan->id)
            ->where('farms.0.scan.disease', $ownScan->disease->name)
            ->where('farms.0.scan.scan_date', '2026-10-01')
            ->missing('farms.0.name')
            ->missing('farms.0.contact_number')
            ->missing('farms.1')
            ->missing('farms.1.scan'));

    $response
        ->assertDontSee('Private Other Farmer Name')
        ->assertDontSee('09999999999')
        ->assertDontSee('2026-10-02');
    expect($otherScan->id)->not->toBe($ownScan->id);
});

test('the map does not identify a former farmer municipality for staff', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Pilar)
        ->create(['user_id' => $user->id]);
    $user->forceFill(['role' => UserRole::LguStaff])->save();

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->where('ownMunicipality', null));
});

test('farmers see only active outbreaks and their own farm pin', function () {
    $ownFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create(['farm_lat' => 14.52, 'farm_long' => 120.48]);
    $otherFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orani)
        ->create(['farm_lat' => 14.62, 'farm_long' => 120.58]);
    $ownActiveOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Active,
    ]);
    $otherActiveOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orani,
        'status' => OutbreakStatusEnum::Active,
    ]);
    Alert::factory()->for($otherActiveOutbreak)->create(['severity' => SeverityEnum::Low]);
    Alert::factory()->for($otherActiveOutbreak)->create(['severity' => SeverityEnum::High]);
    Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Resolved,
    ]);
    Scan::factory()->for($otherFarm, 'farmer')->create([
        'outbreak_id' => $otherActiveOutbreak->id,
    ]);

    $this->actingAs($ownFarm->user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 1)
            ->where('outbreaks.0.id', $ownActiveOutbreak->id)
            ->where('outbreaks.0.municipality', 'orion')
            ->where('outbreaks.0.disease', $ownActiveOutbreak->disease->name)
            ->missing('outbreaks.1')
            ->has('municipalityStatuses', 12)
            ->where('municipalityStatuses.8', [
                'municipality' => 'orani',
                'severity' => 'high',
            ])
            ->where('municipalityStatuses.9', [
                'municipality' => 'orion',
                'severity' => 'low',
            ])
            ->where('ownMunicipality', 'orion')
            ->has('farms', 1)
            ->where('farms.0.id', $ownFarm->id)
            ->missing('farms.1'));
});

test('admins see every farm with coordinates and its latest scan', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activeFarm = FarmerProfile::factory()->create([
        'farm_lat' => 14.52,
        'farm_long' => 120.48,
    ]);
    $resolvedFarm = FarmerProfile::factory()->create();
    $unlinkedFarm = FarmerProfile::factory()->create();
    FarmerProfile::factory()->create();
    $activeOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Active]);
    $resolvedOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Resolved]);

    Scan::factory()->for($activeFarm, 'farmer')->create([
        'outbreak_id' => $activeOutbreak->id,
        'scan_date' => '2026-10-03 10:00:00',
    ]);
    $activeScan = Scan::factory()->for($activeFarm, 'farmer')->create([
        'outbreak_id' => $activeOutbreak->id,
        'scan_date' => '2026-10-04 10:00:00',
    ]);
    Scan::factory()->for($resolvedFarm, 'farmer')->create([
        'outbreak_id' => $resolvedOutbreak->id,
    ]);
    Scan::factory()->for($unlinkedFarm, 'farmer')->create();

    $this->actingAs($admin)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 1)
            ->where('outbreaks.0.id', $activeOutbreak->id)
            ->has('farms', 4)
            ->where('farms.0.id', $activeFarm->id)
            ->where('farms.0.name', $activeFarm->full_name)
            ->where('farms.0.contact_number', $activeFarm->contact_number)
            ->where('farms.0.barangay', $activeFarm->barangay)
            ->where('farms.0.municipality', $activeFarm->municipality->value)
            ->where('farms.0.scan.id', $activeScan->id)
            ->where('farms.0.scan.disease', $activeScan->disease->name)
            ->where('farms.0.scan.scan_date', '2026-10-04')
            ->where('farms.0.lat', 14.52)
            ->where('farms.0.lng', 120.48)
            ->where('farms.1.id', $resolvedFarm->id)
            ->where('farms.1.scan.disease', $resolvedFarm->scans()->first()->disease->name)
            ->where('farms.2.id', $unlinkedFarm->id)
            ->where('farms.3.scan', null));
});

test('LGU staff still see only farms with scans linked to active outbreaks', function () {
    $lguStaff = User::factory()->create(['role' => UserRole::LguStaff]);
    $activeFarm = FarmerProfile::factory()->create([
        'full_name' => 'Mapped Farmer Name',
        'contact_number' => '09123456789',
    ]);
    $resolvedFarm = FarmerProfile::factory()->create();
    $unlinkedFarm = FarmerProfile::factory()->create();
    $activeOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Active]);
    $resolvedOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Resolved]);
    Scan::factory()->for($activeFarm, 'farmer')->create([
        'outbreak_id' => $activeOutbreak->id,
    ]);
    Scan::factory()->for($resolvedFarm, 'farmer')->create([
        'outbreak_id' => $resolvedOutbreak->id,
    ]);
    Scan::factory()->for($unlinkedFarm, 'farmer')->create();

    $this->actingAs($lguStaff)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('farms', 1)
            ->where('farms.0.id', $activeFarm->id)
            ->where('farms.0.name', 'Mapped Farmer Name')
            ->where('farms.0.contact_number', '09123456789')
            ->missing('farms.1'));
});
