<?php

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Report;
use App\Models\Scan;
use App\Models\User;

it('lets an LGU staff user generate a report', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->actingAs($lgu)
        ->post(route('reports.store'), [
            'municipality' => MunicipalityEnum::Balanga->value,
            'range_start' => '2026-10-01',
            'range_end' => '2026-10-03',
        ])
        ->assertRedirect();

    expect(Report::count())->toBe(1);
});

it('blocks a farmer from reports', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);

    $this->actingAs($farmer)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($farmer)->post(route('reports.store'), [])->assertForbidden();
});

it('counts a scan made late on the end date', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $profile = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Balanga]);

    Scan::factory()->create([
        'farmer_id' => $profile->id,
        'status' => ScanStatusEnum::Completed,
        'scan_date' => '2026-10-03 23:30:00',
    ]);

    $this->actingAs($lgu)->post(route('reports.store'), [
        'municipality' => MunicipalityEnum::Balanga->value,
        'range_start' => '2026-10-01',
        'range_end' => '2026-10-03',
    ]);

    expect(Report::first()->summary_data['total_scans'])->toBe(1);
});
