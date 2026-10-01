<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@kampuslms.test'],
            [
                'name' => 'Admin Kampus',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $admin->role = 'admin';
        $admin->save();

        $lecturer = User::updateOrCreate(
            ['email' => 'dosen@kampuslms.test'],
            [
                'name' => 'Dosen Demo',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $lecturer->role = 'dosen';
        $lecturer->save();

        $student = User::updateOrCreate(
            ['email' => 'mahasiswa@kampuslms.test'],
            [
                'name' => 'Mahasiswa Demo',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $student->role = 'mahasiswa';
        $student->save();
    }
}