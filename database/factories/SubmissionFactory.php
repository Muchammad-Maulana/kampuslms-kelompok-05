<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
        $now = now();
        $isLate = $assignment->due_at->lt($now->copy()->subSecond()) && fake()->boolean(40);
        $endTimestamp = $isLate
            ? $now->timestamp
            : min($now->timestamp, $assignment->due_at->timestamp - 1);
        $startTimestamp = $isLate
            ? $assignment->due_at->timestamp + 1
            : $endTimestamp - (30 * 24 * 60 * 60);
        $submittedAt = Carbon::createFromTimestamp(random_int($startTimestamp, $endTimestamp));

        return [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->course->students->random()->id,
            'file_path' => 'submissions/demo.pdf',
            'original_name' => 'demo.pdf',
            'file_size' => fake()->numberBetween(1000, 500000),
            'note' => fake()->optional()->sentence(),
            'submitted_at' => $submittedAt,
            'is_late' => $isLate,
        ];
    }
}
