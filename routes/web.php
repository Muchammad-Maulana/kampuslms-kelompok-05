<?php 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
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

Route::get('/login', [AuthController::class, 'create'])
    ->name('login');

Route::post('/login', [AuthController::class, 'store'])
    ->name('login.store');

Route::post('/logout', [AuthController::class, 'destroy'])
    ->name('logout');

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
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
});

Route::prefix('dosen')->name('dosen.')->middleware('role:dosen')->group(function () {
    Route::get('/mata-kuliah', [CourseController::class, 'index'])
        ->name('mata-kuliah.index');

    Route::get('/mata-kuliah/create', [CourseController::class, 'create'])
        ->name('mata-kuliah.create');

    Route::post('/mata-kuliah', [CourseController::class, 'store'])
        ->name('mata-kuliah.store');

    Route::get('/mata-kuliah/{course}/edit', [CourseController::class, 'edit'])
        ->name('mata-kuliah.edit');

    Route::put('/mata-kuliah/{course}', [CourseController::class, 'update'])
        ->name('mata-kuliah.update');

    Route::delete('/mata-kuliah/{course}', [CourseController::class, 'destroy'])
        ->name('mata-kuliah.destroy');

    Route::get('/mata-kuliah/{course}', [CourseController::class, 'show'])
        ->name('mata-kuliah.show');
});

Route::prefix('mahasiswa')->name('mahasiswa.')->middleware('role:mahasiswa')->group(function () {
    Route::get('/mata-kuliah', [CourseController::class, 'index'])
        ->name('mata-kuliah.index');

    Route::get('/mata-kuliah/{course}', [CourseController::class, 'show'])
        ->name('mata-kuliah.show');
});

// Resource materi dan tugas bersarang karena keduanya dimiliki oleh satu mata kuliah.
Route::scopeBindings()->group(function () {
    Route::resource('mata-kuliah.materi', MaterialController::class)
        ->parameters(['mata-kuliah' => 'course', 'materi' => 'material'])
        ->shallow();

    Route::resource('mata-kuliah.tugas', AssignmentController::class)
        ->parameters(['mata-kuliah' => 'course', 'tugas' => 'assignment'])
        ->shallow();

    // URL nested ini mempertahankan konteks course agar binding child dapat di-scope.
    Route::get('mata-kuliah/{course}/materi/{material}', [MaterialController::class, 'showInCourse'])
        ->name('mata-kuliah.materi.in-course');

    Route::get('mata-kuliah/{course}/tugas/{assignment}', [AssignmentController::class, 'showInCourse'])
        ->name('mata-kuliah.tugas.in-course');
});

Route::get('/error', function () { 
    abort(404); 
})->name('error');


