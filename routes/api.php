<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::post('/mata-kuliah', [CourseController::class, 'apiStore']);
