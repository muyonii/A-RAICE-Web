<?php

namespace Database\Seeders;

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use App\Services\OutbreakDetectionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiTestSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = $this->farmer('API Farmer One', 'api-farmer@test.local', 'Tuyo', MunicipalityEnum::Balanga, '09170000001', 14.6760, 120.5360);
        $farmer2 = $this->farmer('API Farmer Two', 'api-farmer2@test.local', 'Wawa', MunicipalityEnum::Orion, '09170000002', 14.6200, 120.5800);
        $lgu = $this->user('API LGU Staff', 'api-lgu@test.local', UserRole::LguStaff);
        $admin = $this->user('API Admin', 'api-admin@test.local', UserRole::Admin);

        $alertFarmers = [
            $farmer,
            $this->farmer('API Alert Farmer Two', 'api-alert-farmer2@test.local', 'Tuyo', MunicipalityEnum::Balanga, '09170000003', 14.6770, 120.5370),
            $this->farmer('API Alert Farmer Three', 'api-alert-farmer3@test.local', 'Tuyo', MunicipalityEnum::Balanga, '09170000004', 14.6780, 120.5380),
        ];

        $disease = Disease::query()->updateOrCreate(
            ['name' => 'API Smoke Test Disease'],
            [
                'description' => 'Local API smoke-test disease record.',
                'prevention_tips' => 'Local test data only.',
            ],
        );

        $scans = [];
        foreach ($alertFarmers as $index => $profile) {
            $scans[] = Scan::query()->updateOrCreate(
                ['image_url' => 'api-test/alert-seed-'.($index + 1).'.jpg'],
                [
                    'farmer_id' => $profile->id,
                    'uploaded_by_id' => $profile->user_id,
                    'disease_id' => $disease->id,
                    'outbreak_id' => null,
                    'confidence_score' => 0.95,
                    'raw_predictions' => [],
                    'status' => ScanStatusEnum::Completed,
                    'gps_lat' => $profile->farm_lat,
                    'gps_long' => $profile->farm_long,
                    'scan_date' => now(),
                ],
            );
        }

        $outbreaks = app(OutbreakDetectionService::class);
        foreach ($scans as $scan) {
            $outbreaks->handle($scan);
        }

        $outbreak = Outbreak::query()
            ->where('disease_id', $disease->id)
            ->where('municipality', MunicipalityEnum::Balanga)
            ->firstOrFail();
        $alert = $outbreak->alerts()->latest('id')->firstOrFail();
        $alert->users()->updateExistingPivot([
            $farmer->user_id,
            $alertFarmers[1]->user_id,
            $alertFarmers[2]->user_id,
            $lgu->id,
            $admin->id,
        ], ['read_at' => null]);
    }

    private function farmer(
        string $name,
        string $email,
        string $barangay,
        MunicipalityEnum $municipality,
        string $contactNumber,
        float $farmLat,
        float $farmLong,
    ): FarmerProfile {
        $user = $this->user($name, $email, UserRole::Farmer);

        return FarmerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $name,
                'barangay' => $barangay,
                'municipality' => $municipality,
                'contact_number' => $contactNumber,
                'farm_lat' => $farmLat,
                'farm_long' => $farmLong,
            ],
        );
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ],
        );

        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
