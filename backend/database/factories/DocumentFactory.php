<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'file_path' => 'documents/' . Str::uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(1000, 10000000),
            'mime_type' => 'application/pdf',
            'page_count' => fake()->numberBetween(1, 100),
            'extracted_text' => fake()->paragraphs(3, true),
            'source_type' => 'pdf',
            'status' => 'completed',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'extracted_text' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'extracted_text' => null,
        ]);
    }

    public function youtube(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'youtube',
            'file_path' => null,
            'file_name' => null,
            'mime_type' => null,
        ]);
    }
}
