<?php

namespace Database\Factories;

use App\Enums\SeverityEnum;
use App\Models\Alert;
use App\Models\Outbreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'outbreak_id' => Outbreak::factory(),
            'message' => fake()->sentence(),
            'severity' => fake()->randomElement(SeverityEnum::cases()),
        ];
    }
}
