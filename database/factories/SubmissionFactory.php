<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assignment = Assignment::query()
            ->with('course.students')
            ->inRandomOrder()
            ->firstOrFail();

        return [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->course->students->random()->id,
            'file_path' => 'submissions/demo.pdf',
            'original_name' => 'demo.pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'note' => fake()->optional()->sentence(),
            'submitted_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'is_late' => false,
        ];
    }
}
