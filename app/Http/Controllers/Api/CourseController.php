<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): object
    {
        $query = match ($request->user()->role) {
            'admin' => Course::query(),
            'dosen' => $request->user()->taughtCourses(),
            'mahasiswa' => $request->user()->courses(),
            default => abort(403),
        };

        $query->with('lecturer')->withCount(['materials', 'assignments']);

        if ($request->filled('q')) {
            $query->where(function ($courseQuery) use ($request) {
                $keyword = trim($request->input('q'));
                $courseQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $courses = $query->latest('created_at')->paginate(15);

        return CourseResource::collection($courses);
    }

    public function show(Request $request, Course $course): CourseResource
    {
        $this->authorizeCourseRead($request->user(), $course);

        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorizeCourseManage($request->user());

        $course = Course::create($request->validated());
        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return (new CourseResource($course))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $this->authorizeCourseManage($request->user());

        $course->update($request->validated());
        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    public function destroy(Request $request, Course $course): \Illuminate\Http\Response
    {
        $this->authorizeCourseManage($request->user());
        $course->delete();

        return response()->noContent();
    }

    private function authorizeCourseRead($user, Course $course): void
    {
        $allowed = match ($user->role) {
            'admin' => true,
            'dosen' => $course->lecturer_id === $user->id,
            'mahasiswa' => $user->courses()->whereKey($course->getKey())->exists(),
            default => false,
        };

        abort_unless($allowed, 403);
    }

    private function authorizeCourseManage($user): void
    {
        abort_unless($user->role === 'admin', 403);
    }
}
