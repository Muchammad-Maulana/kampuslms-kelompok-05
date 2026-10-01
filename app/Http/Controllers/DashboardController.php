<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $courses = match ($user->role) {
            'admin' => Course::query(),
            'dosen' => $user->taughtCourses(),
            'mahasiswa' => $user->courses(),
            default => abort(403),
        };

        $courseCount = (clone $courses)->count('courses.id');
        $totalSks = (clone $courses)->sum('courses.sks');
        $recentCourses = $courses->with('lecturer')->latest('courses.created_at')->limit(5)->get();
        $courseIndexRoute = match ($user->role) {
            'admin' => 'admin.mata-kuliah.index',
            'dosen' => 'dosen.mata-kuliah.index',
            'mahasiswa' => 'mahasiswa.mata-kuliah.index',
        };

        return view('dashboard', compact('courseCount', 'totalSks', 'recentCourses', 'courseIndexRoute'));
    }
}
