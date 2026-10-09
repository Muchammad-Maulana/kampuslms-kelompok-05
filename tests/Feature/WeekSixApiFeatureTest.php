<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Database\Factories\AssignmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekSixApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_course_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/courses');

        $response->assertUnauthorized();
    }

    public function test_login_does_not_reveal_whether_email_exists(): void
    {
        User::factory()->create(['email' => 'known@kampuslms.test']);

        $knownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'known@kampuslms.test',
            'password' => 'wrong-password',
        ]);
        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@kampuslms.test',
            'password' => 'wrong-password',
        ]);

        $knownEmail->assertUnprocessable();
        $unknownEmail->assertUnprocessable();
        $this->assertSame($knownEmail->status(), $unknownEmail->status());
        $this->assertSame(
            ['Email atau password salah.'],
            $knownEmail->json('errors.email')
        );
        $this->assertSame(
            $knownEmail->json('errors.email'),
            $unknownEmail->json('errors.email')
        );
    }

    public function test_login_returns_sanctum_token_and_whitelisted_user(): void
    {
        $user = User::factory()->create([
            'email' => 'api-user@kampuslms.test',
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'feature-test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonMissing(['password' => $user->password])
            ->assertJsonMissing(['remember_token']);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'feature-test',
        ]);
    }

    public function test_login_is_limited_to_five_attempts_per_minute(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'unknown@kampuslms.test',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@kampuslms.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_me_returns_a_whitelisted_user_without_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'role' => 'mahasiswa',
        ]);

        $this->withToken($user->createToken('me-test')->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role']])
            ->assertJsonMissing(['password', 'remember_token']);
    }

    public function test_model_serialization_demo_exposes_only_sensitive_field_names(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $this->withToken($user->createToken('demo-test')->plainTextToken)
            ->getJson('/api/v1/security/model-serialization-demo')
            ->assertOk()
            ->assertJsonPath('unsafe_model.sensitive_fields_that_must_not_be_returned', ['password', 'remember_token'])
            ->assertJsonPath('safe_resource.email', $user->email)
            ->assertJsonMissingPath('safe_resource.password')
            ->assertJsonMissingPath('safe_resource.remember_token');
    }

    public function test_raw_users_demo_alias_uses_the_safe_serialization_response(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $this->withToken($user->createToken('raw-users-demo')->plainTextToken)
            ->getJson('/api/v1/demo/raw-users')
            ->assertOk()
            ->assertJsonMissingPath('safe_resource.password')
            ->assertJsonMissingPath('safe_resource.remember_token');
    }

    public function test_protected_course_endpoint_distinguishes_401_and_403(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->getJson('/api/v1/courses/'.$course->id)
            ->assertUnauthorized();

        $this->withToken($student->createToken('authorization-demo')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id)
            ->assertForbidden();
    }

    public function test_student_cannot_create_course(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/v1/courses', [
                'code' => 'API100',
                'name' => 'Course API',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_course_with_rest_status_codes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $token = $admin->createToken('test')->plainTextToken;

        $created = $this->withToken($token)
            ->postJson('/api/v1/courses', [
                'code' => 'API100',
                'name' => 'Course API',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ]);

        $created->assertCreated();
        $courseId = $created->json('data.id');

        $this->withToken($token)
            ->putJson('/api/v1/courses/'.$courseId, [
                'code' => 'API100',
                'name' => 'Course API Updated',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Course API Updated');

        $this->withToken($token)
            ->deleteJson('/api/v1/courses/'.$courseId)
            ->assertNoContent();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $token = $user->createToken('test')->plainTextToken;
        $tokenId = $user->tokens()->where('name', 'test')->value('id');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_api_resource_returns_only_public_course_fields(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'name' => 'API Course Test',
        ]);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($student);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        $response->assertJsonPath('data.0.id', $course->id);
        $response->assertJsonPath('data.0.code', $course->code);
        $response->assertJsonPath('data.0.name', $course->name);
        $response->assertJsonMissing(['password', 'remember_token']);
    }

    public function test_student_cannot_access_course_for_another_student(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id);

        $response->assertForbidden();
    }

    public function test_student_cannot_list_assignments_for_a_course_they_do_not_take(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id.'/assignments');

        $response->assertForbidden();
    }

    public function test_lecturer_cannot_list_assignments_for_another_lecturers_course(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $response = $this->withToken($lecturer->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id.'/assignments');

        $response->assertForbidden();
    }

    public function test_student_cannot_create_assignment(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/v1/courses/'.$course->id.'/assignments', [
                'title' => 'Tugas baru',
                'instructions' => 'Kerjakan tugas.',
                'due_at' => now()->addDay()->toIso8601String(),
            ]);

        $response->assertForbidden();
    }

    public function test_course_lecturer_can_create_and_delete_assignment(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $token = $lecturer->createToken('test')->plainTextToken;

        $created = $this->withToken($token)
            ->postJson('/api/v1/courses/'.$course->id.'/assignments', [
                'title' => 'Tugas API',
                'instructions' => 'Kerjakan tugas API.',
                'due_at' => now()->addDay()->toIso8601String(),
            ]);

        $created->assertCreated();
        $assignmentId = $created->json('data.id');

        $this->withToken($token)
            ->putJson('/api/v1/courses/'.$course->id.'/assignments/'.$assignmentId, [
                'title' => 'Tugas API diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Tugas API diperbarui');

        $this->withToken($token)
            ->deleteJson('/api/v1/courses/'.$course->id.'/assignments/'.$assignmentId)
            ->assertNoContent();
    }

    public function test_student_cannot_update_or_delete_assignment(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = AssignmentFactory::new()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/courses/'.$course->id.'/assignments/'.$assignment->id, [
                'title' => 'Tidak boleh diubah',
            ])
            ->assertForbidden();

        $this->withToken($token)
            ->deleteJson('/api/v1/courses/'.$course->id.'/assignments/'.$assignment->id)
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => $assignment->title,
        ]);
    }

    public function test_nested_assignment_list_filters_draft_assignments_for_student(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student);
        $assignment = AssignmentFactory::new()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'draft',
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id.'/assignments');

        $response->assertOk();
        $response->assertJsonMissing(['id' => $assignment->id]);
    }

    public function test_api_assignment_detail_rejects_mismatched_course(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = AssignmentFactory::new()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);
        $course->students()->attach($student);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/v1/courses/'.$course->id.'/assignments/'.$assignment->id);

        $response->assertNotFound();
    }
}
