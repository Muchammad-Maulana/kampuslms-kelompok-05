<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::query()->inRandomOrder()->value('id'),
            'title' => fake()->randomElement([
                'Analisis Kebutuhan Sistem',
                'Perancangan Basis Data',
                'Implementasi Fitur Aplikasi',
                'Dokumentasi Proyek',
                'Presentasi Hasil Pengembangan',
            ]),
            'instructions' => fake()->paragraph(2),
            'created_by' => Course::query()->with('lecturer')->inRandomOrder()->firstOrFail()->lecturer_id,
            'due_at' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'max_score' => fake()->numberBetween(50, 100),
            'status' => 'published',
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'status' => 'published',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'status' => 'published',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'status' => 'draft',
        ]);
    }
}
