<?php

namespace Database\Factories;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Models\Disease;
use App\Models\Outbreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outbreak>
 */
class OutbreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disease_id' => Disease::factory(),
            'municipality' => fake()->randomElement(MunicipalityEnum::cases()),
            'status' => OutbreakStatusEnum::Active,
        ];
    }
}
