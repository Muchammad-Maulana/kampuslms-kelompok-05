<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('SI######'),
            'name' => fake()->randomElement([
                'Pemrograman Web',
                'Basis Data',
                'Analisis dan Perancangan Sistem Informasi',
                'Rekayasa Perangkat Lunak',
                'Manajemen Proyek Teknologi Informasi',
                'Jaringan Komputer',
                'Kecerdasan Buatan',
            ]),
            'description' => fake()->sentence(12),
            'sks' => fake()->randomElement([2, 3, 4]),
            'lecturer_id' => User::query()
                ->where('role', 'dosen')
                ->inRandomOrder()
                ->value('id'),
            'status' => fake()->randomElement(['draft', 'active', 'archived']),
        ];
    }
}
