<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $courses = Course::query()->visibleTo($user);

        $courseCount = (clone $courses)->count('courses.id');
        $recentCourses = $courses->with('lecturer')->latest('courses.created_at')->limit(5)->get();
        $roleStats = match ($user->role) {
            'admin' => [
                ['label' => 'Pengguna aktif', 'value' => User::query()->count()],
                ['label' => 'Mata kuliah', 'value' => $courseCount],
                ['label' => 'Tugas', 'value' => Assignment::query()->count()],
            ],
            'dosen' => [
                ['label' => 'Mata kuliah diampu', 'value' => $courseCount],
                ['label' => 'Tugas terbit', 'value' => Assignment::query()
                    ->where('status', 'published')
                    ->whereHas('course', fn ($query) => $query->where('lecturer_id', $user->id))
                    ->count()],
                ['label' => 'Pengumpulan belum dinilai', 'value' => Submission::query()
                    ->whereDoesntHave('grade')
                    ->whereHas('assignment.course', fn ($query) => $query->where('lecturer_id', $user->id))
                    ->count()],
            ],
            'mahasiswa' => [
                ['label' => 'Mata kuliah diikuti', 'value' => $courseCount],
                ['label' => 'Tugas terbit', 'value' => Assignment::query()
                    ->where('status', 'published')
                    ->whereHas('course.students', fn ($query) => $query->whereKey($user->id))
                    ->count()],
                ['label' => 'Tugas dikumpulkan', 'value' => $user->submissions()->count()],
            ],
        };
        $courseIndexRoute = match ($user->role) {
            'admin' => 'admin.mata-kuliah.index',
            'dosen' => 'dosen.mata-kuliah.index',
            'mahasiswa' => 'mahasiswa.mata-kuliah.index',
        };

        return view('dashboard', compact('roleStats', 'recentCourses', 'courseIndexRoute'));
    }
}
