<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'admin@kampuslms.test',
                'name' => 'Admin Kampus',
                'role' => 'admin',
                'password' => config('demo.admin_password'),
            ],
            [
                'email' => 'dosen@kampuslms.test',
                'name' => 'Dosen Demo',
                'role' => 'dosen',
                'password' => config('demo.lecturer_password'),
            ],
            [
                'email' => 'mahasiswa@kampuslms.test',
                'name' => 'Mahasiswa Demo',
                'role' => 'mahasiswa',
                'password' => config('demo.student_password'),
            ],
        ];

        if (app()->isProduction()) {
            $passwords = array_column($accounts, 'password');

            if (
                in_array(null, $passwords, true)
                || count(array_unique($passwords)) !== count($passwords)
                || collect($passwords)->contains(
                    fn (string $password): bool => strlen($password) < 16 || $password === 'password'
                )
            ) {
                throw new RuntimeException(
                    'Production demo accounts require three distinct passwords of at least 16 characters.'
                );
            }
        }

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'] ?? 'password',
                    'email_verified_at' => now(),
                ]
            );
            $user->role = $account['role'];
            $user->save();
        }
    }
}
