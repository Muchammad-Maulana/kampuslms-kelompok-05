<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
Route::get('/tentang', fn () => view('tentang'))->name('tentang');

Route::resource('mata-kuliah', CourseController::class)
    ->parameters(['mata-kuliah' => 'course'])
    ->names('mata-kuliah');

Route::resource('users', UserController::class)->names('users');

Route::get('/error', fn () => abort(404))->name('error');
