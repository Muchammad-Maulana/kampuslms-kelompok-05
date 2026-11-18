<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_see_course_leaderboard_ranked_by_timely_submissions_then_percentage_with_badges(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $topStudent = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Alya']);
        $percentageTieBreaker = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Bima']);
        $lowerStudent = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Citra']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach([$topStudent->id, $percentageTieBreaker->id, $lowerStudent->id]);

        $assignments = collect(range(1, 10))->map(
            fn () => $this->createAssignment($course, $lecturer, 'published', now()->subDay())
        );

        foreach ($assignments as $assignment) {
            $this->createSubmission($assignment, $topStudent, false);
        }

        $this->createSubmission($assignments[0], $percentageTieBreaker, false);
        $this->createSubmission($assignments[1], $percentageTieBreaker, false);
        $this->createSubmission($assignments[2], $percentageTieBreaker, true);

        $this->createSubmission($assignments[0], $lowerStudent, false);
        $this->createSubmission($assignments[1], $lowerStudent, false);

        $draftAssignment = $this->createAssignment($course, $lecturer, 'draft', now()->subDay());
        $this->createSubmission($draftAssignment, $percentageTieBreaker, false);

        $futureAssignment = $this->createAssignment($course, $lecturer, 'published', now()->addDay());
        $this->createSubmission($futureAssignment, $percentageTieBreaker, false);

        $this->actingAs($lowerStudent)
            ->get(route('mahasiswa.mata-kuliah.leaderboard', $course))
            ->assertOk()
            ->assertSeeInOrder(['Alya', 'Citra', 'Bima'])
            ->assertSee('Teladan')
            ->assertSee('Konsisten')
            ->assertSee('Perintis')
            ->assertSee('66.7%')
            ->assertSee('100.0%');
    }

    public function test_only_enrolled_students_and_the_course_lecturer_can_view_the_leaderboard(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student->id);

        $this->actingAs($student)
            ->get(route('mahasiswa.mata-kuliah.leaderboard', $course))
            ->assertOk();

        $this->actingAs($lecturer)
            ->get(route('dosen.mata-kuliah.leaderboard', $course))
            ->assertOk();

        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.mata-kuliah.leaderboard', $course))
            ->assertForbidden();

        $this->actingAs($otherLecturer)
            ->get(route('dosen.mata-kuliah.leaderboard', $course))
            ->assertForbidden();
    }

    private function createSubmission(Assignment $assignment, User $student, bool $isLate): void
    {
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/'.$assignment->id.'/'.$student->id.'.pdf',
            'original_name' => 'jawaban.pdf',
            'file_size' => 100,
            'submitted_at' => $assignment->due_at->copy()->addHours($isLate ? 1 : -1),
            'is_late' => $isLate,
        ]);
    }

    private function createAssignment(Course $course, User $lecturer, string $status, \DateTimeInterface $dueAt): Assignment
    {
        return Assignment::create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Latihan pengumpulan',
            'instructions' => 'Kerjakan dan kumpulkan.',
            'due_at' => $dueAt,
            'max_score' => 100,
            'allow_late' => true,
            'status' => $status,
        ]);
    }
}
