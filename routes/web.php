<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/dashboard', fn () => view('dashboard'))->middleware('auth')->name('dashboard');
Route::get('/tentang', fn () => view('tentang'))->name('tentang');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('mata-kuliah', CourseController::class)
    ->parameters(['mata-kuliah' => 'course'])
    ->names('mata-kuliah');

    Route::resource('users', UserController::class)->names('users');
});

Route::get('/error', fn () => abort(404))->name('error');
