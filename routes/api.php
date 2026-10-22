<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.auth.login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');

        Route::get('/courses', [CourseController::class, 'index'])
            ->name('api.courses.index');
        Route::post('/courses', [CourseController::class, 'store'])
            ->name('api.courses.store');
        Route::get('/courses/{course}', [CourseController::class, 'show'])
            ->name('api.courses.show');
        Route::match(['put', 'patch'], '/courses/{course}', [CourseController::class, 'update'])
            ->name('api.courses.update');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])
            ->name('api.courses.destroy');

        Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])
            ->name('api.assignments.index');
        Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])
            ->name('api.assignments.store');
        Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
            ->name('api.assignments.show');
        Route::match(['put', 'patch'], '/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'update'])
            ->name('api.assignments.update');
        Route::delete('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'destroy'])
            ->name('api.assignments.destroy');
    });
});
