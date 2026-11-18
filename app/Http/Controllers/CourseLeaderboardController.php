<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class CourseLeaderboardController extends Controller
{
    private const BADGES = [
        ['name' => 'Perintis', 'threshold' => 3],
        ['name' => 'Konsisten', 'threshold' => 5],
        ['name' => 'Teladan', 'threshold' => 10],
    ];

    public function show(Course $course)
    {
        $this->authorize('view', $course);

        $dueAssignmentIds = Assignment::query()
            ->where('course_id', $course->id)
            ->where('status', 'published')
            ->where('due_at', '<=', now())
            ->select('id');

        $students = $course->students()
            ->where('users.role', 'mahasiswa')
            ->select(['users.id', 'users.name'])
            ->withCount([
                'submissions as submitted_count' => fn ($query) => $query
                    ->whereIn('assignment_id', $dueAssignmentIds),
                'submissions as on_time_count' => fn ($query) => $query
                    ->where('is_late', false)
                    ->whereIn('assignment_id', $dueAssignmentIds),
            ])
            ->get()
            ->map(function (User $student): User {
                $student->on_time_percentage = $student->submitted_count > 0
                    ? round(($student->on_time_count / $student->submitted_count) * 100, 1)
                    : 0;
                $student->badges = collect(self::BADGES)
                    ->filter(fn (array $badge): bool => $student->on_time_count >= $badge['threshold'])
                    ->values();

                return $student;
            })
            ->sort(function (User $first, User $second): int {
                return ($second->on_time_count <=> $first->on_time_count)
                    ?: ($second->on_time_percentage <=> $first->on_time_percentage)
                    ?: strcasecmp($first->name, $second->name);
            })
            ->values()
            ->each(function (User $student, int $index): void {
                $student->rank = $index + 1;
            });

        return view('courses.leaderboard', compact('course', 'students'));
    }
}
