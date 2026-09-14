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
            'description' => fake()->paragraph(2),
            'due_at' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'max_score' => fake()->numberBetween(50, 100),
        ];
    }
}
