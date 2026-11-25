<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoCourseContentSeeder extends Seeder
{
    private const COURSES = [
        [
            'code' => 'DEMO-IF101',
            'name' => 'Pemrograman Web',
            'description' => 'Contoh mata kuliah untuk demonstrasi alur KampusLMS.',
            'sks' => 3,
            'assignments' => [
                [
                    'title' => 'Membuat rancangan halaman web',
                    'instructions' => 'Buat rancangan halaman web responsif dan jelaskan struktur komponennya.',
                    'due_days' => -14,
                    'submission' => true,
                    'score' => 88,
                    'feedback' => 'Struktur halaman sudah jelas dan tampilan responsif. Perhatikan konsistensi jarak antar elemen.',
                ],
                [
                    'title' => 'Membangun fitur formulir',
                    'instructions' => 'Implementasikan formulir dengan validasi sisi server dan pesan kesalahan yang mudah dipahami.',
                    'due_days' => -7,
                    'submission' => true,
                    'score' => null,
                    'feedback' => null,
                ],
                [
                    'title' => 'Presentasi aplikasi web',
                    'instructions' => 'Siapkan demo singkat yang menjelaskan fitur, validasi, dan alur pengguna.',
                    'due_days' => 7,
                    'submission' => false,
                    'score' => null,
                    'feedback' => null,
                ],
            ],
        ],
        [
            'code' => 'DEMO-IF202',
            'name' => 'Basis Data',
            'description' => 'Contoh mata kuliah basis data dengan tugas dan hasil penilaian demo.',
            'sks' => 3,
            'assignments' => [
                [
                    'title' => 'Perancangan ERD sistem perpustakaan',
                    'instructions' => 'Rancang ERD yang mencakup buku, anggota, peminjaman, dan pengembalian.',
                    'due_days' => -14,
                    'submission' => true,
                    'score' => 92,
                    'feedback' => 'Relasi dan kardinalitas sudah tepat. Tambahkan penjelasan aturan integritas data.',
                ],
                [
                    'title' => 'Query laporan peminjaman',
                    'instructions' => 'Buat query untuk menampilkan riwayat peminjaman beserta identitas anggota dan buku.',
                    'due_days' => -7,
                    'submission' => true,
                    'score' => null,
                    'feedback' => null,
                ],
                [
                    'title' => 'Optimisasi query',
                    'instructions' => 'Identifikasi query yang dapat dioptimalkan dan jelaskan pilihan indeksnya.',
                    'due_days' => 7,
                    'submission' => false,
                    'score' => null,
                    'feedback' => null,
                ],
            ],
        ],
        [
            'code' => 'DEMO-IF203',
            'name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Contoh mata kuliah rekayasa perangkat lunak untuk akun demo.',
            'sks' => 3,
            'assignments' => [
                [
                    'title' => 'Analisis kebutuhan aplikasi',
                    'instructions' => 'Tuliskan kebutuhan fungsional dan nonfungsional untuk aplikasi yang dipilih.',
                    'due_days' => -14,
                    'submission' => true,
                    'score' => 85,
                    'feedback' => 'Kebutuhan utama sudah teridentifikasi. Buat kriteria penerimaan lebih terukur.',
                ],
                [
                    'title' => 'Rencana pengujian fitur',
                    'instructions' => 'Buat skenario pengujian jalur sukses, validasi gagal, dan otorisasi.',
                    'due_days' => -7,
                    'submission' => true,
                    'score' => null,
                    'feedback' => null,
                ],
                [
                    'title' => 'Demo hasil proyek',
                    'instructions' => 'Demonstrasikan alur utama dan jelaskan keputusan desain yang digunakan.',
                    'due_days' => 7,
                    'submission' => false,
                    'score' => null,
                    'feedback' => null,
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $lecturer = User::query()->where('email', 'dosen@kampuslms.test')->first();
        $student = User::query()->where('email', 'mahasiswa@kampuslms.test')->first();

        if (! $lecturer || $lecturer->role !== 'dosen' || ! $student || $student->role !== 'mahasiswa') {
            throw new RuntimeException(
                'DemoCourseContentSeeder requires existing dosen@kampuslms.test and mahasiswa@kampuslms.test accounts with their expected roles.'
            );
        }

        $submissionFiles = DB::transaction(function () use ($lecturer, $student): array {
            $files = [];

            foreach (self::COURSES as $courseData) {
                $course = Course::query()->firstOrCreate(
                    ['code' => $courseData['code']],
                    [
                        'name' => $courseData['name'],
                        'description' => $courseData['description'],
                        'sks' => $courseData['sks'],
                        'lecturer_id' => $lecturer->id,
                        'status' => 'active',
                    ]
                );

                if ($course->lecturer_id !== $lecturer->id) {
                    throw new RuntimeException(
                        "Demo course {$courseData['code']} already belongs to a different lecturer; no existing data was changed."
                    );
                }

                $course->students()->syncWithoutDetaching([$student->id]);

                foreach ($courseData['assignments'] as $index => $assignmentData) {
                    $assignment = Assignment::query()->firstOrCreate(
                        [
                            'course_id' => $course->id,
                            'title' => $assignmentData['title'],
                        ],
                        [
                            'created_by' => $lecturer->id,
                            'instructions' => $assignmentData['instructions'],
                            'due_at' => now()->addDays($assignmentData['due_days']),
                            'max_score' => 100,
                            'allow_late' => true,
                            'status' => 'published',
                        ]
                    );

                    if ($assignment->created_by !== $lecturer->id) {
                        throw new RuntimeException(
                            "Demo assignment '{$assignment->title}' already belongs to a different lecturer; no existing data was changed."
                        );
                    }

                    if (! $assignmentData['submission']) {
                        continue;
                    }

                    $filePath = sprintf(
                        'submissions/demo-content/%s-assignment-%d.txt',
                        strtolower($courseData['code']),
                        $index + 1
                    );
                    $fileContents = "Contoh jawaban untuk tugas: {$assignment->title}\n";
                    $submission = Submission::query()->firstOrCreate(
                        [
                            'assignment_id' => $assignment->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'file_path' => $filePath,
                            'original_name' => 'jawaban-demo.txt',
                            'file_size' => strlen($fileContents),
                            'note' => 'Pengumpulan contoh untuk demonstrasi KampusLMS.',
                            'submitted_at' => $assignment->due_at->copy()->subDay(),
                            'is_late' => false,
                        ]
                    );

                    $files[$submission->file_path] = $fileContents;

                    if ($assignmentData['score'] !== null) {
                        Grade::query()->firstOrCreate(
                            ['submission_id' => $submission->id],
                            [
                                'graded_by' => $lecturer->id,
                                'score' => $assignmentData['score'],
                                'feedback' => $assignmentData['feedback'],
                                'graded_at' => now()->subDays(3),
                            ]
                        );
                    }
                }
            }

            return $files;
        });

        $disk = Storage::disk('local');
        foreach ($submissionFiles as $path => $contents) {
            if (! $disk->exists($path)) {
                $disk->put($path, $contents);
            }
        }
    }
}
