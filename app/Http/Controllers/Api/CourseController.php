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
        $this->authorize('viewAny', Course::class);

        $query = Course::query()->visibleTo($request->user());

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
        $this->authorize('view', $course);

        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $course = Course::create($request->validated());
        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return (new CourseResource($course))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $this->authorize('update', $course);

        $course->update($request->validated());
        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    public function destroy(Request $request, Course $course): \Illuminate\Http\Response
    {
        $this->authorize('delete', $course);
        $course->delete();

        return response()->noContent();
    }
}
