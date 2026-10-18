<?php

namespace Database\Factories;

use App\Enums\ScanStatusEnum;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farmer_id' => FarmerProfile::factory(),
            'uploaded_by_id' => User::factory(),
            'disease_id' => Disease::factory(),
            'image_url' => 'scans/test.jpg',
            'confidence_score' => 0.95,
            'raw_predictions' => [],
            'status' => ScanStatusEnum::Completed,
            'gps_lat' => 14.5,
            'gps_long' => 120.5,
            'scan_date' => now(),
        ];
    }
}
