<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\DemoCourseContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DemoCourseContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_course_content_is_added_idempotently_for_existing_demo_accounts(): void
    {
        Storage::fake('local');

        $lecturer = User::factory()->create([
            'email' => 'dosen@kampuslms.test',
            'role' => 'dosen',
        ]);
        $student = User::factory()->create([
            'email' => 'mahasiswa@kampuslms.test',
            'role' => 'mahasiswa',
        ]);
        $unrelatedCourse = Course::factory()->create();

        $seeder = app(DemoCourseContentSeeder::class);
        $seeder->run();

        $firstDemoCourse = Course::query()->where('code', 'DEMO-IF101')->firstOrFail();
        $firstDemoCourse->update(['name' => 'Nama yang sudah disesuaikan']);
        $firstGrade = Grade::query()->firstOrFail();
        $firstGrade->update(['score' => 77]);

        $seeder->run();

        $this->assertSame(4, Course::query()->count());
        $this->assertSame(
            3,
            Course::query()->where('lecturer_id', $lecturer->id)->where('code', 'like', 'DEMO-%')->count()
        );
        $this->assertSame(9, Assignment::query()->count());
        $this->assertSame(6, Submission::query()->count());
        $this->assertSame(3, Grade::query()->count());
        $this->assertSame('Nama yang sudah disesuaikan', $firstDemoCourse->fresh()->name);
        $this->assertSame('77.00', $firstGrade->fresh()->score);
        $this->assertSame(1, $firstDemoCourse->students()->whereKey($student->id)->count());
        $this->assertSame(1, Course::query()->whereKey($unrelatedCourse->id)->count());
        $this->assertSame(6, count(Storage::disk('local')->allFiles('submissions/demo-content')));
    }

    public function test_demo_course_content_seeder_requires_existing_demo_accounts(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires existing dosen@kampuslms.test and mahasiswa@kampuslms.test');

        app(DemoCourseContentSeeder::class)->run();
    }
}
