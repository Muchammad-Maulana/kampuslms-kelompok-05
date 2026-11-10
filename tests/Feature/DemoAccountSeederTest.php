<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DemoAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_requires_three_unique_strong_passwords(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Production demo accounts require three distinct passwords of at least 16 characters.'
        );

        $this->app->make(DemoAccountSeeder::class)->run();
    }

    public function test_production_seeding_hashes_the_configured_demo_passwords(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config([
            'app.env' => 'production',
            'demo.admin_password' => 'Admin-secret-70a6be67',
            'demo.lecturer_password' => 'Lecturer-secret-35d672b1',
            'demo.student_password' => 'Student-secret-0e91c45d',
        ]);

        $this->app->make(DemoAccountSeeder::class)->run();

        $this->assertTrue(Hash::check(
            'Admin-secret-70a6be67',
            User::where('email', 'admin@kampuslms.test')->value('password')
        ));
        $this->assertTrue(Hash::check(
            'Lecturer-secret-35d672b1',
            User::where('email', 'dosen@kampuslms.test')->value('password')
        ));
        $this->assertTrue(Hash::check(
            'Student-secret-0e91c45d',
            User::where('email', 'mahasiswa@kampuslms.test')->value('password')
        ));
    }
}
