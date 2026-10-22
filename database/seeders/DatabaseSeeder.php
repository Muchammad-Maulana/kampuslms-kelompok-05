<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Database\Factories\AssignmentFactory;
use Database\Factories\CourseFactory;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoAccountSeeder::class);

        User::factory(2)->create([
            'role' => 'dosen',
        ]);

        User::factory(29)->create([
            'role' => 'mahasiswa',
        ]);

        $lecturers = User::query()
            ->where('role', 'dosen')
            ->get();
        $students = User::query()
            ->where('role', 'mahasiswa')
            ->get();

        for ($courseNumber = 0; $courseNumber < 5; $courseNumber++) {
            /** @var Course $course */
            $course = CourseFactory::new()->create([
                'lecturer_id' => $lecturers->random()->id,
            ]);

            $course->students()->sync(
                $students->random(16)->modelKeys()
            );

            AssignmentFactory::new()->overdue()->create(['course_id' => $course->id]);
            AssignmentFactory::new()->active()->create(['course_id' => $course->id]);
            AssignmentFactory::new()->draft()->create(['course_id' => $course->id]);
        }

        $submissionPairs = Course::query()
            ->with('assignments')
            ->get()
            ->flatMap(function (Course $course) {
                $studentIds = $course->students()->pluck('users.id');

                return $course->assignments->flatMap(function ($assignment) use ($studentIds) {
                    return $studentIds->map(fn (int $studentId) => [
                        'assignment_id' => $assignment->id,
                        'user_id' => $studentId,
                    ]);
                });
            })
            ->shuffle()
            ->take(100);

        foreach ($submissionPairs as $submissionPair) {
            SubmissionFactory::new()->create($submissionPair);
        }

        $submissionsToGrade = Submission::query()
            ->whereDoesntHave('grade')
            ->inRandomOrder()
            ->limit(60)
            ->get();

        foreach ($submissionsToGrade as $submission) {
            Grade::create([
                'submission_id' => $submission->id,
                'graded_by' => $submission->assignment->course->lecturer_id,
                'score' => fake()->numberBetween(0, 100),
                'feedback' => fake()->sentence(),
                'graded_at' => now(),
            ]);
        }
    }
}
