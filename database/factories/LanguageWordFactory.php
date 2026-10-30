<?php

namespace Database\Factories;

use App\Models\LanguageWord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LanguageWord>
 */
class LanguageWordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'word' => fake()->unique()->lexify('????'),
            'meaning' => fake()->word(),
        ];
    }
}
