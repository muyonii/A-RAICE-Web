<?php

namespace Database\Factories;

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FarmerProfile>
 */
class FarmerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Farmer]),
            'full_name' => fake()->name(),
            'barangay' => fake()->streetName(),
            'municipality' => fake()->randomElement(MunicipalityEnum::cases()),
            'contact_number' => '09'.fake()->numerify('#########'),
            'farm_lat' => fake()->randomFloat(6, 14.4, 14.8),
            'farm_long' => fake()->randomFloat(6, 120.4, 120.6),
        ];
    }

    public function inMunicipality(MunicipalityEnum $municipality): static
    {
        return $this->state(['municipality' => $municipality]);
    }
}
