<?php

use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('farmers receive farmer dashboard props without staff stats', function () {
    $farmer = FarmerProfile::factory()->create();
    $otherFarmer = FarmerProfile::factory()->create();
    $disease = Disease::factory()->create();

    Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed,
    ]);
    Scan::factory()->for($otherFarmer, 'farmer')->create();

    $this->actingAs($farmer->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('scans_count', 1)
            ->missing('stats')
            ->missing('admin_stats')
            ->has('recent_scans', 1)
            ->where('recent_scans.0.disease', $disease->name)
            ->missing('recent_scans.0.farmer_name')
            ->has('active_outbreak'));
});

test('LGU staff receive province-wide stats and recent scans without farmer scan count', function () {
    $staff = User::factory()->create(['role' => UserRole::LguStaff]);
    $farmer = FarmerProfile::factory()->create();
    $disease = Disease::factory()->create();
    $activeOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Active]);
    Outbreak::factory()->create(['status' => OutbreakStatusEnum::Resolved]);

    Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed,
        'scan_date' => now()->subDays(2),
    ]);
    Scan::factory()->for($farmer, 'farmer')->create([
        'scan_date' => now()->subDays(8),
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('scans_count')
            ->missing('admin_stats')
            ->where('stats.active_outbreaks', 1)
            ->where('stats.scans_last_7_days', 1)
            ->has('recent_scans', 2)
            ->where('recent_scans.0.disease', $disease->name)
            ->where('recent_scans.0.farmer_name', $farmer->full_name)
            ->has('recent_scans.0.status')
            ->has('recent_scans.0.confidence_score')
            ->has('recent_scans.0.scan_date')
            ->where('recent_scans.1.farmer_name', $farmer->full_name)
            ->missing('active_outbreak'));
});

test('admins receive staff dashboard props without farmer scan count', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Disease::factory()->count(2)->create();
    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('scans_count')
            ->where('stats.active_outbreaks', 0)
            ->where('stats.scans_last_7_days', 0)
            ->where('admin_stats.users_count', 4)
            ->where('admin_stats.diseases_count', 2)
            ->has('recent_scans', 0)
            ->missing('active_outbreak'));
});
