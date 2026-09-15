<?php 
use App\Http\Controllers\CourseController; 
use Illuminate\Support\Facades\Route; 

Route::get('/', function () { 
    return view('welcome'); 
})->name('home'); 

Route::get('/dashboard', function () { 
    return view('dashboard'); 
})->name('dashboard'); 

Route::get('/tentang', function () { 
    return view('tentang'); 
})->name('tentang'); 

Route::get('/mata-kuliah', [CourseController::class, 'index']) 
    ->name('mata-kuliah.index'); 

Route::get('/mata-kuliah/create', [CourseController::class, 'create'])
    ->name('mata-kuliah.create');

Route::post('/mata-kuliah', [CourseController::class, 'store'])
    ->name('mata-kuliah.store');

Route::get('/mata-kuliah/{id}/edit', [CourseController::class, 'edit'])
    ->name('mata-kuliah.edit');

Route::put('/mata-kuliah/{id}', [CourseController::class, 'update'])
    ->name('mata-kuliah.update');

Route::delete('/mata-kuliah/{id}', [CourseController::class, 'destroy'])
    ->name('mata-kuliah.destroy');
    
// Route untuk menampilkan detail satu mata kuliah. 
Route::get('/mata-kuliah/{id}', [CourseController::class, 'show']) 
    ->name('mata-kuliah.show'); 

Route::get('/error', function () { 
    abort(404); 
})->name('error');


