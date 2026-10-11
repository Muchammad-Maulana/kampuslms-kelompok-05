<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');
Route::get('/tentang', fn () => view('tentang'))->name('tentang');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('mata-kuliah', CourseController::class)
        ->parameters(['mata-kuliah' => 'course'])
        ->names('mata-kuliah');

    Route::resource('users', UserController::class)->names('users');
    Route::post('users/{user}/password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password');

    Route::scopeBindings()->group(function () {
        Route::resource('mata-kuliah.materials', App\Http\Controllers\MaterialController::class)
            ->parameters(['mata-kuliah' => 'course', 'materials' => 'material'])
            ->shallow();
        Route::resource('mata-kuliah.assignments', App\Http\Controllers\AssignmentController::class)
            ->parameters(['mata-kuliah' => 'course', 'assignments' => 'assignment'])
            ->shallow();
        Route::get('mata-kuliah/{course}/materials/{material}', [App\Http\Controllers\MaterialController::class, 'showScoped'])
            ->name('mata-kuliah.materials.scoped-show');
        Route::get('mata-kuliah/{course}/assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'showScoped'])
            ->name('mata-kuliah.assignments.scoped-show');
    });
});

Route::middleware(['auth', 'role:dosen'])->prefix('dosen')->name('dosen.')->group(function () {
    Route::get('mata-kuliah', [CourseController::class, 'index'])->name('mata-kuliah.index');
    Route::get('mata-kuliah/{course}', [CourseController::class, 'show'])->name('mata-kuliah.show');

    Route::scopeBindings()->group(function () {
        Route::resource('mata-kuliah.materials', App\Http\Controllers\MaterialController::class)
            ->parameters(['mata-kuliah' => 'course', 'materials' => 'material'])
            ->shallow();
        Route::resource('mata-kuliah.assignments', App\Http\Controllers\AssignmentController::class)
            ->parameters(['mata-kuliah' => 'course', 'assignments' => 'assignment'])
            ->shallow();
        Route::get('mata-kuliah/{course}/materials/{material}', [App\Http\Controllers\MaterialController::class, 'showScoped'])
            ->name('mata-kuliah.materials.scoped-show');
        Route::get('mata-kuliah/{course}/assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'showScoped'])
            ->name('mata-kuliah.assignments.scoped-show');
    });
});

Route::middleware(['auth', 'role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('mata-kuliah', [CourseController::class, 'index'])->name('mata-kuliah.index');
    Route::get('mata-kuliah/{course}', [CourseController::class, 'show'])->name('mata-kuliah.show');

    Route::scopeBindings()->group(function () {
        Route::get('mata-kuliah/{course}/materials/{material}', [App\Http\Controllers\MaterialController::class, 'showScoped'])
            ->name('mata-kuliah.materials.scoped-show');
        Route::get('mata-kuliah/{course}/assignments/{assignment}', [App\Http\Controllers\AssignmentController::class, 'showScoped'])
            ->name('mata-kuliah.assignments.scoped-show');
        Route::resource('mata-kuliah.materials', App\Http\Controllers\MaterialController::class)
            ->only(['index', 'show'])
            ->parameters(['mata-kuliah' => 'course', 'materials' => 'material'])
            ->shallow();
        Route::resource('mata-kuliah.assignments', App\Http\Controllers\AssignmentController::class)
            ->only(['index', 'show'])
            ->parameters(['mata-kuliah' => 'course', 'assignments' => 'assignment'])
            ->shallow();
    });
});

Route::get('/error', fn () => abort(404))->name('error');
