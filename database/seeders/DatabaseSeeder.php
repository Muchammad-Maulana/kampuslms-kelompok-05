<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Database\Factories\AssignmentFactory;
use Database\Factories\CourseFactory;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoAccountSeeder::class);

        $lecturerCount = User::query()->where('role', 'dosen')->count();
        User::factory(max(0, 3 - $lecturerCount))->create(['role' => 'dosen']);

        $studentCount = User::query()->where('role', 'mahasiswa')->count();
        User::factory(max(0, 30 - $studentCount))->create(['role' => 'mahasiswa']);

        $lecturers = User::query()
            ->where('role', 'dosen')
            ->get();
        $students = User::query()
            ->where('role', 'mahasiswa')
            ->get();
        $demoStudent = User::where('email', 'mahasiswa@kampuslms.test')->firstOrFail();
        $courses = Course::query()->orderBy('id')->get();

        for ($courseNumber = $courses->count(); $courseNumber < 5; $courseNumber++) {
            /** @var Course $course */
            CourseFactory::new()->create([
                'lecturer_id' => $lecturers->random()->id,
            ]);
        }

        $courses = Course::query()->orderBy('id')->take(5)->get();

        foreach ($courses as $courseNumber => $course) {
            $enrolledStudentIds = $course->students()->pluck('users.id');
            $studentsToEnroll = collect();

            if ($courseNumber === 0 && ! $enrolledStudentIds->contains($demoStudent->id)) {
                $studentsToEnroll->push($demoStudent->id);
            } elseif ($courseNumber > 0 && $enrolledStudentIds->contains($demoStudent->id)) {
                $course->students()->detach($demoStudent->id);
                $enrolledStudentIds = $enrolledStudentIds->reject(
                    fn (int $studentId) => $studentId === $demoStudent->id
                )->values();
            }

            $missingEnrollments = max(0, 15 - $enrolledStudentIds->count() - $studentsToEnroll->count());
            if ($missingEnrollments > 0) {
                $availableStudentIds = $students
                    ->where('id', '!=', $demoStudent->id)
                    ->whereNotIn('id', $enrolledStudentIds->merge($studentsToEnroll)->all())
                    ->pluck('id');
                $studentsToEnroll = $studentsToEnroll->merge($availableStudentIds->random($missingEnrollments));
            }

            $course->students()->syncWithoutDetaching($studentsToEnroll->all());

            $assignments = $course->assignments()->get();
            $hasOverdueAssignment = $assignments->contains(
                fn ($assignment) => $assignment->status === 'published' && $assignment->due_at->isPast()
            );
            $hasActiveAssignment = $assignments->contains(
                fn ($assignment) => $assignment->status === 'published' && $assignment->due_at->isFuture()
            );
            $hasDraftAssignment = $assignments->contains(fn ($assignment) => $assignment->status === 'draft');

            if (! $hasOverdueAssignment) {
                AssignmentFactory::new()->overdue()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);
            }

            if (! $hasActiveAssignment) {
                AssignmentFactory::new()->active()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);
            }

            if (! $hasDraftAssignment) {
                AssignmentFactory::new()->draft()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                ]);
            }
        }

        $assignmentsById = Assignment::query()->get()->keyBy('id');
        $existingSubmissionKeys = Submission::query()
            ->get(['assignment_id', 'user_id'])
            ->mapWithKeys(fn (Submission $submission) => [
                $submission->assignment_id.':'.$submission->user_id => true,
            ]);
        $submissionsNeeded = max(0, 100 - Submission::query()->count());
        $submissionPairs = Course::query()
            ->with('assignments')
            ->get()
            ->flatMap(function (Course $course) {
                $studentIds = $course->students()->pluck('users.id');

                return $course->assignments
                    ->where('status', 'published')
                    ->flatMap(function (Assignment $assignment) use ($studentIds) {
                    return $studentIds->map(fn (int $studentId) => [
                        'assignment_id' => $assignment->id,
                        'user_id' => $studentId,
                    ]);
                });
            })
            ->reject(fn (array $pair) => $existingSubmissionKeys->has($pair['assignment_id'].':'.$pair['user_id']))
            ->shuffle()
            ->take($submissionsNeeded);

        foreach ($submissionPairs as $submissionPair) {
            $assignment = $assignmentsById->get($submissionPair['assignment_id']);
            $now = now();
            $isLate = $assignment->due_at->lt($now->copy()->subSecond()) && fake()->boolean(40);
            $endTimestamp = $isLate
                ? $now->timestamp
                : min($now->timestamp, $assignment->due_at->timestamp - 1);
            $startTimestamp = $isLate
                ? $assignment->due_at->timestamp + 1
                : $endTimestamp - (30 * 24 * 60 * 60);
            $submittedAt = Carbon::createFromTimestamp(random_int($startTimestamp, $endTimestamp));

            $submission = SubmissionFactory::new()->make($submissionPair);
            $submission->submitted_at = $submittedAt;
            $submission->is_late = $isLate;
            $submission->save();
        }

        $gradesNeeded = max(0, (int) ceil(Submission::query()->count() * 0.6) - Grade::query()->count());
        $submissionsToGrade = Submission::query()
            ->whereDoesntHave('grade')
            ->inRandomOrder()
            ->limit($gradesNeeded)
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
