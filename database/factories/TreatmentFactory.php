<?php

namespace Database\Factories;

use App\Enums\TreatmentTypeEnum;
use App\Models\Disease;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
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
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(TreatmentTypeEnum::cases()),
        ];
    }
}
