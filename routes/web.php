<?php 
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController; 
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

// Route untuk menampilkan detail satu mata kuliah. 
Route::get('/mata-kuliah/{id}', [CourseController::class, 'show']) 
    ->name('mata-kuliah.show'); 

// Route CRUD untuk manajemen Pengguna.
Route::get('/pengguna', [UserController::class, 'index'])
    ->name('pengguna.index');

Route::get('/pengguna/tambah', [UserController::class, 'create'])
    ->name('pengguna.create');

Route::post('/pengguna', [UserController::class, 'store'])
    ->name('pengguna.store');

Route::get('/pengguna/{pengguna}', [UserController::class, 'show'])
    ->name('pengguna.show');

Route::get('/pengguna/{pengguna}/edit', [UserController::class, 'edit'])
    ->name('pengguna.edit');

Route::put('/pengguna/{pengguna}', [UserController::class, 'update'])
    ->name('pengguna.update');

Route::delete('/pengguna/{pengguna}', [UserController::class, 'destroy'])
    ->name('pengguna.destroy');

Route::get('/error', function () { 
    abort(404); 
})->name('error');
