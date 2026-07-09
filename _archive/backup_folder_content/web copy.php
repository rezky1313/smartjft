<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumahsakitController;
use App\Http\Controllers\CentralController;
use App\Http\Controllers\PetaDashboardController;
use App\Http\Controllers\FormasiJabatanController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


route::get('/', [CentralController::class,'login'])->name('login');
Route::get('/logout', [CentralController::class, 'logout'])->name('logout');
Route::get('/error', [CentralController::class,'error']);
Route::get('/sesi', [CentralController::class,'sesi']);

//CONTACT EMAIL
Route::get('/', [App\Http\Controllers\EmailController::class, 'create']);
Route::post('/', [App\Http\Controllers\EmailController::class, 'sendEmail'])->name('send.email');



route::middleware(['guest'])->group(function(){
        route::get('/login',[CentralController::class,'login'])->name('login');
        route::post('/login',[CentralController::class,'loginAksi']);
        route::get('/register',[CentralController::class,'register'])->name('register');
        route::post('/register',[CentralController::class,'registerAksi'])->name('register');
        Route::get('/forgot-password', [CentralController::class,'showLinkRequestForm'])->name('password.request');
        Route::post('/forgot-password', [CentralController::class,'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [CentralController::class,'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [CentralController::class,'reset'])->name('password.update');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Route::resource('/data', RumahsakitController::class)->middleware('userakses:admin');
    Route::get('/', [RumahsakitController::class,'index'])->middleware('userakses:admin')->name('admin.index');
    Route::get('/create', [RumahsakitController::class,'create'])->middleware('userakses:admin')->name('admin.create');
    Route::post('/', [RumahsakitController::class,'store'])->middleware('userakses:admin')->name('admin.store');
    Route::get('/{rumahsakit}', [RumahsakitController::class,'show'])->middleware('userakses:admin')->name('admin.show');
    Route::get('/{rumahsakit}/edit',[RumahsakitController::class,'edit'])->middleware('userakses:admin')->name('admin.edit');
    Route::put('/{rumahsakit}', [RumahsakitController::class,'update'])->middleware('userakses:admin')->name('admin.update');
    Route::delete('/{rumahsakit}', [RumahsakitController::class,'destroy'])->middleware('userakses:admin')->name('admin.destroy');
    Route::resource('formasi-jabatan', FormasiJabatanController::class);

    Route::prefix('/dashboard')->controller(PetaDashboardController::class)->group(function () {
        route::get('/peta','index')->name('peta');
    });

});

Route::middleware(['auth'])->prefix('user')->group(function () {

    Route::get('/', [RumahsakitController::class,'index'])->middleware('userakses:user')->name('user.index');

    Route::prefix('/dashboard')->controller(PetaDashboardController::class)->group(function () {
        route::get('/peta','index')->name('peta');
    });


});




<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumahsakitController;
use App\Http\Controllers\CentralController;
use App\Http\Controllers\PetaDashboardController;
use App\Http\Controllers\FormasiJabatanController;
use App\Http\Controllers\EmailController;

// -------------------- PUBLIC / GUEST --------------------

Route::get('/login', [CentralController::class,'login'])->name('login');
Route::get('/logout', [CentralController::class, 'logout'])->name('logout');
Route::get('/error', [CentralController::class,'error']);
Route::get('/sesi', [CentralController::class,'sesi']);

//Route::get('/contact', [EmailController::class, 'create']);
//Route::post('/contact', [EmailController::class, 'sendEmail'])->name('send.email');

Route::middleware(['guest'])->group(function() {
    Route::get('/', [CentralController::class,'login'])->name('login');
    Route::post('/login', [CentralController::class,'loginAksi']);
    Route::get('/register', [CentralController::class,'register'])->name('register');
    Route::post('/register', [CentralController::class,'registerAksi'])->name('register');
    Route::get('/forgot-password', [CentralController::class,'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [CentralController::class,'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [CentralController::class,'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [CentralController::class,'reset'])->name('password.update');
});

// -------------------- ADMIN --------------------

Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Rumah Sakit = Unit Kerja
    Route::get('/', [RumahsakitController::class, 'index'])->middleware('userakses:admin')->name('rumahsakit.index');
    Route::get('/create', [RumahsakitController::class, 'create'])->middleware('userakses:admin')->name('rumahsakit.create');
    Route::post('/', [RumahsakitController::class, 'store'])->middleware('userakses:admin')->name('rumahsakit.store');
    Route::get('/{rumahsakit}', [RumahsakitController::class, 'show'])->middleware('userakses:admin')->name('rumahsakit.show');
    Route::get('/{rumahsakit}/edit', [RumahsakitController::class, 'edit'])->middleware('userakses:admin')->name('rumahsakit.edit');
    Route::put('/{rumahsakit}', [RumahsakitController::class, 'update'])->middleware('userakses:admin')->name('rumahsakit.update');
    Route::delete('/{rumahsakit}', [RumahsakitController::class, 'destroy'])->middleware('userakses:admin')->name('rumahsakit.destroy');

    // Formasi Jabatan
    Route::resource('formasi-jabatan', FormasiJabatanController::class)->middleware('userakses:admin');

    // Peta Dashboard
    Route::prefix('dashboard')->controller(PetaDashboardController::class)->group(function () {
        Route::get('/peta', 'index')->name('dashboard.peta');
    });

});

// -------------------- USER --------------------

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    Route::get('/', [RumahsakitController::class, 'index'])->middleware('userakses:user')->name('index');
    Route::prefix('dashboard')->controller(PetaDashboardController::class)->group(function () {
        Route::get('/peta', 'index')->name('dashboard.peta');
    });
});
