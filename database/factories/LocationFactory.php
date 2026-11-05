<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->paragraph(),
        ];
    }

    public function withMap(): static
    {
        return $this->state(fn (): array => ['map_image_path' => 'maps/'.fake()->unique()->lexify('????????').'.png']);
    }

    public function pinnedOn(Location $parent, float $x = 50, float $y = 50): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->id, 'pin_x' => $x, 'pin_y' => $y]);
    }
}
