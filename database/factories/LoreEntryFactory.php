<?php

namespace Database\Factories;

use App\Models\LoreEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoreEntry>
 */
class LoreEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(array_keys(LoreEntry::CATEGORIES)),
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->paragraph(),
        ];
    }
}
