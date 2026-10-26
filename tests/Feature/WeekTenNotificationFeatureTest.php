<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\GradeAwarded;
use App\Listeners\NotifyStudentOfGradeAwarded;
use App\Listeners\NotifyStudentsOfPublishedAssignment;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\GradeReleasedNotification;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeekTenNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_an_assignment_queues_a_listener_but_draft_assignments_do_not(): void
    {
        Queue::fake();

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.store', $course), $this->assignmentData('draft'))
            ->assertRedirect();

        $this->assertSame(0, $this->queuedAssignmentListenerCount());

        $draft = Assignment::query()->firstOrFail();
        $this->actingAs($lecturer)
            ->put(route('dosen.assignments.update', $draft), $this->assignmentData('published'))
            ->assertRedirect();

        $this->assertSame(1, $this->queuedAssignmentListenerCount());

        $published = Assignment::query()->create(array_merge($this->assignmentData('published'), [
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]));
        AssignmentPublished::dispatch($published);

        $this->assertSame(2, $this->queuedAssignmentListenerCount());
    }

    public function test_assignment_listener_queues_database_notifications_for_only_enrolled_students(): void
    {
        Notification::fake();

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = $this->createAssignment($course, $lecturer);

        app(NotifyStudentsOfPublishedAssignment::class)->handle(new AssignmentPublished($assignment));

        Notification::assertSentTo($student, NewAssignmentNotification::class);
        Notification::assertNotSentTo($otherStudent, NewAssignmentNotification::class);

        $payload = (new NewAssignmentNotification($assignment))->toDatabase($student);
        $this->assertSame('assignment_published', $payload['kind']);
        $this->assertSame($assignment->id, $payload['assignment_id']);
        $this->assertArrayNotHasKey('score', $payload);
        $this->assertArrayNotHasKey('feedback', $payload);
        $this->assertArrayNotHasKey('instructions', $payload);
    }

    public function test_database_worker_processes_assignment_listener_and_notifications(): void
    {
        config(['queue.default' => 'database']);

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $students = User::factory()->count(2)->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($students);
        $assignment = $this->createAssignment($course, $lecturer);

        AssignmentPublished::dispatch($assignment);

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('notifications', 0);

        $this->artisan('queue:work', ['--once' => true, '--tries' => 3])->assertExitCode(0);

        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('notifications', 0);

        $this->artisan('queue:work', ['--once' => true, '--tries' => 3])->assertExitCode(0);
        $this->artisan('queue:work', ['--once' => true, '--tries' => 3])->assertExitCode(0);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $students[0]->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $students[1]->id,
        ]);
    }

    public function test_grade_listener_notifies_only_submission_owner_without_grade_or_feedback_data(): void
    {
        Notification::fake();

        [$student, $otherStudent, $grade] = $this->createGradeContext();
        $this->assertNotNull($grade->submission->user);

        app(NotifyStudentOfGradeAwarded::class)->handle(new GradeAwarded($grade));

        Notification::assertSentTo($student, GradeReleasedNotification::class);
        Notification::assertNotSentTo($otherStudent, GradeReleasedNotification::class);

        $payload = (new GradeReleasedNotification($grade))->toDatabase($student);
        $this->assertSame('grade_released', $payload['kind']);
        $this->assertSame($grade->submission_id, $payload['submission_id']);
        $this->assertArrayNotHasKey('score', $payload);
        $this->assertArrayNotHasKey('feedback', $payload);
    }

    public function test_grading_a_submission_dispatches_a_queued_grade_listener(): void
    {
        Queue::fake();

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = $this->createAssignment($course, $lecturer);
        $submission = $this->createSubmission($assignment, $student);

        $this->actingAs($lecturer)
            ->post(route('dosen.mata-kuliah.assignments.submissions.grade', [$course, $assignment, $submission]), [
                'score' => 91,
                'feedback' => 'Pekerjaan yang baik.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 91,
            'feedback' => 'Pekerjaan yang baik.',
        ]);
        $this->assertSame(
            1,
            Queue::pushed(CallQueuedListener::class)
                ->filter(fn (CallQueuedListener $job): bool => $job->class === NotifyStudentOfGradeAwarded::class)
                ->count()
        );
    }

    public function test_database_notification_payload_does_not_store_grade_or_feedback(): void
    {
        [$student, , $grade] = $this->createGradeContext();

        Notification::sendNow($student, new GradeReleasedNotification($grade));

        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $student->id)
            ->firstOrFail();
        $payload = json_decode($storedNotification->data, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('grade_released', $payload['kind']);
        $this->assertArrayNotHasKey('score', $payload);
        $this->assertArrayNotHasKey('feedback', $payload);
        $this->assertDatabaseHas('notifications', [
            'id' => $storedNotification->id,
            'notifiable_type' => User::class,
            'notifiable_id' => $student->id,
        ]);
    }

    public function test_student_can_mark_all_owned_notifications_as_read(): void
    {
        $owner = User::factory()->create(['role' => 'mahasiswa']);
        $otherUser = User::factory()->create(['role' => 'mahasiswa']);
        $ownedId = $this->insertNotification($owner);
        $foreignId = $this->insertNotification($otherUser);

        $this->actingAs($owner)
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull(DB::table('notifications')->where('id', $ownedId)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $foreignId)->value('read_at'));
    }

    public function test_notifications_are_private_and_the_unread_count_is_scoped_to_the_signed_in_user(): void
    {
        $owner = User::factory()->create(['role' => 'mahasiswa']);
        $otherUser = User::factory()->create(['role' => 'mahasiswa']);
        $ownedId = $this->insertNotification($owner);
        $foreignId = $this->insertNotification($otherUser);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)
            ->get(route('notifications.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Assignment '.$ownedId)
            ->assertDontSee('Assignment '.$foreignId);

        $this->actingAs($owner)
            ->post(route('notifications.read', $foreignId))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => $foreignId,
            'notifiable_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('notifications.read', $ownedId))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'id' => $ownedId,
            'notifiable_id' => $owner->id,
        ]);
        $this->assertNotNull(DB::table('notifications')->where('id', $ownedId)->value('read_at'));

        $this->actingAs($admin)
            ->get(route('notifications.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    }

    public function test_student_grade_report_only_contains_the_students_own_grades(): void
    {
        [$student, $otherStudent, $grade] = $this->createGradeContext();
        $otherSubmission = $this->createSubmission(
            $grade->submission->assignment,
            $otherStudent,
        );
        $otherGrade = Grade::query()->create([
            'submission_id' => $otherSubmission->id,
            'graded_by' => $grade->graded_by,
            'score' => 12,
            'feedback' => 'Feedback milik mahasiswa lain',
            'graded_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.grades.index'))
            ->assertOk()
            ->assertSee($grade->feedback)
            ->assertDontSee($otherGrade->feedback);
    }

    public function test_course_grade_report_is_limited_to_the_course_lecturer(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $this->actingAs($lecturer)
            ->get(route('dosen.mata-kuliah.grades', $course))
            ->assertOk();

        $this->actingAs($lecturer)
            ->get(route('dosen.mata-kuliah.grades', $otherCourse))
            ->assertForbidden();
    }

    private function assignmentData(string $status): array
    {
        return [
            'title' => 'Assignment '.$status,
            'instructions' => 'Instructions',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'allow_late' => true,
            'status' => $status,
        ];
    }

    private function createAssignment(Course $course, User $lecturer): Assignment
    {
        return Assignment::query()->create(array_merge($this->assignmentData('published'), [
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]));
    }

    private function createGradeContext(): array
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach([$student->id, $otherStudent->id]);
        $assignment = $this->createAssignment($course, $lecturer);
        $submission = $this->createSubmission($assignment, $student);
        $grade = Grade::query()->create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
            'score' => 87,
            'feedback' => 'Feedback pribadi untuk mahasiswa',
            'graded_at' => now(),
        ]);

        return [$student, $otherStudent, $grade];
    }

    private function createSubmission(Assignment $assignment, User $student): Submission
    {
        return Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size' => 100,
            'submitted_at' => now(),
            'is_late' => false,
        ]);
    }

    private function insertNotification(User $user): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => NewAssignmentNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'kind' => 'assignment_published',
                'assignment_title' => 'Assignment '.$id,
                'course_name' => 'Private course',
                'url' => route('dashboard'),
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function queuedAssignmentListenerCount(): int
    {
        return Queue::pushed(CallQueuedListener::class)
            ->filter(fn (CallQueuedListener $job): bool => $job->class === NotifyStudentsOfPublishedAssignment::class)
            ->count();
    }
}
