<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeReportController extends Controller
{
    public function lecturerCourse(Request $request, Course $course): View
    {
        $this->authorize('view', $course);

        $course->load('students:id,name');
        $assignments = $course->assignments()
            ->with(['submissions.user', 'submissions.grade'])
            ->orderBy('due_at')
            ->get();

        return view('grades.lecturer-course', compact('course', 'assignments'));
    }

    public function studentIndex(Request $request): View
    {
        $grades = Grade::query()
            ->whereHas('submission', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['submission.assignment.course'])
            ->latest('graded_at')
            ->paginate(15);

        return view('grades.student-index', compact('grades'));
    }
}
