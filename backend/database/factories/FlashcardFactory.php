<?php

namespace Database\Factories;

use App\Models\Flashcard;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Flashcard>
 */
class FlashcardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'front_text' => fake()->sentence() . '?',
            'back_text' => fake()->sentence(),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'times_reviewed' => 0,
            'times_correct' => 0,
            'last_reviewed_at' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'times_reviewed' => fake()->numberBetween(1, 10),
            'times_correct' => fake()->numberBetween(0, 5),
            'last_reviewed_at' => now(),
        ]);
    }

    public function easy(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'easy',
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'medium',
        ]);
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'hard',
        ]);
    }
}
