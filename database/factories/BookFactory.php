<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'languages' => [Book::DEFAULT_LANGUAGE],
            'summary' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
        ];
    }
}
