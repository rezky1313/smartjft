<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumahsakitController;
use App\Http\Controllers\CentralController;
use App\Http\Controllers\PetaDashboardController;
use App\Http\Controllers\FormasiJabatanController;
use App\Http\Controllers\SdmController;
use App\Http\Controllers\wilayahController;
use App\Http\Controllers\UjiKompetensiController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UjikomController;
use App\Http\Controllers\PengangkatanController;


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
// Route::get('/', [App\Http\Controllers\EmailController::class, 'create']);
// Route::post('/', [App\Http\Controllers\EmailController::class, 'sendEmail'])->name('send.email');



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




//Route Formasi Jabatan
Route::middleware(['auth'])->prefix('user')->as('user.')->group(function () {

Route::get('laporan/pemangku-simple',
    [\App\Http\Controllers\ReportController::class, 'pemangkuSimple']
)->name('reports.pemangku.simple');


    // AJAX: cari SDM untuk dropdown (Select2)
        Route::get('promotions/sdm-search', [PromotionController::class,'sdmSearch'])->name('promotions.sdm-search');
     
        Route::get('promotions/',             [PromotionController::class,'index'])->name('promotions.index');
        Route::get('promotions/create',       [PromotionController::class,'create'])->name('promotions.create');
        Route::post('promotions/',            [PromotionController::class,'store'])->name('promotions.store');

        // >>> Tambahkan rute file (HARUS di atas {id})
    Route::get('promotions/files/{file}', [PromotionController::class,'fileInline'])
        ->name('promotions.files.inline')->whereNumber('file');
    Route::get('promotions/files/{file}/download', [PromotionController::class,'fileDownload'])
        ->name('promotions.files.download')->whereNumber('file');

        Route::get('promotions/{id}',         [PromotionController::class,'show'])->name('promotions.show');

        Route::post('promotions/{id}/submit', [PromotionController::class,'submit'])->name('promotions.submit');       // pemangku
        Route::post('promotions/{id}/return', [PromotionController::class,'returnBack'])->name('promotions.return');   // admin
        Route::post('promotions/{id}/verify', [PromotionController::class,'verify'])->name('promotions.verify');       // admin
        Route::post('promotions/{id}/approve',[PromotionController::class,'approve'])->name('promotions.approve');     // admin (isi SK/TMT optional)
        Route::post('promotions/{id}/apply',  [PromotionController::class,'apply'])->name('promotions.apply');         // admin (update SDM)
   
        // AJAX: cari SDM untuk dropdown (Select2)
        Route::get('promotions/sdm-search', [PromotionController::class,'sdmSearch'])->name('promotions.sdm-search');


   // A. canonical route with params
    Route::get('formasi/edit-group/{unit}/{tahun}',
        [\App\Http\Controllers\FormasiJabatanController::class, 'editGroup']
    )->name('formasi.edit-group')->whereNumber('unit');

    // B. safety net: if someone hits /user/formasi/edit-group (no params),
    //    redirect back with a message instead of hitting the controller with 0 args
    Route::get('formasi/edit-group',
        function () { return redirect()->route('user.formasi.index')
            ->with('error','Pilih Unit & Tahun dulu sebelum Edit Grup.'); });

    Route::post('formasi/update-group',
        [\App\Http\Controllers\FormasiJabatanController::class, 'updateGroup'])
        ->name('formasi.update-group');

         Route::get('formasi/histori', [FormasiJabatanController::class, 'history'])
        ->name('user.formasi.history');

 
        Route::get('sdm/trash',          [SdmController::class, 'trash'])->name('sdm.trash');
        Route::patch('sdm/{id}/restore', [SdmController::class, 'restore'])->name('sdm.restore');
        Route::delete('sdm/{id}/force',  [SdmController::class, 'forceDelete'])->name('sdm.force-delete');


        Route::get   ('unitkerja/trash',            [RumahsakitController::class,'trash'])->name('unitkerja.trash');
        Route::patch ('unitkerja/{id}/restore',     [RumahsakitController::class,'restore'])->name('unitkerja.restore');
        Route::delete('unitkerja/{id}/force',       [RumahsakitController::class,'forceDelete'])->name('unitkerja.force-delete');

        Route::get   ('formasi/trash',               [FormasiJabatanController::class,'trash'])->name('formasi.trash');
        Route::patch ('formasi/{id}/restore',        [FormasiJabatanController::class,'restore'])->name('formasi.restore');
        Route::delete('formasi/{id}/force',          [FormasiJabatanController::class,'forceDelete'])->name('formasi.force-delete');

        Route::get   ('uji/trash',                   [UjiKompetensiController::class,'trash'])->name('uji.trash');
        Route::patch ('uji/{id}/restore',            [UjiKompetensiController::class,'restore'])->name('uji.restore');
        Route::delete('uji/{id}/force',              [UjiKompetensiController::class,'forceDelete'])->name('uji.force-delete');

        Route::get ('formasi/import-pivot', [FormasiJabatanController::class, 'importPivotForm'])->name('formasi.import-pivot.form');
        Route::post('formasi/import-pivot', [FormasiJabatanController::class, 'importPivotStore'])->name('formasi.import-pivot.store');

        Route::get ('sdm/import', [\App\Http\Controllers\SdmController::class, 'importForm'])->name('sdm.import.form');
        Route::post('sdm/import', [\App\Http\Controllers\SdmController::class, 'importStore'])->name('sdm.import.store');

             
        Route::resource('formasi', \App\Http\Controllers\FormasiJabatanController::class) -> except(['show']);
        Route::resource('unitkerja', \App\Http\Controllers\RumahsakitController::class);
        Route::resource('sdm', \App\Http\Controllers\SdmController::class);

        // Manajemen User - Hanya untuk super_admin
        Route::middleware(['role:super_admin'])->prefix('manajemen-user')->as('manajemen-user.')->group(function() {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

        // Edit Formasi per Unit & Tahun (form multi-baris)
        Route::get('formasi/edit-group', [\App\Http\Controllers\FormasiJabatanController::class, 'editGroup'])
        ->name('formasi.edit-group');

        // Simpan perubahan formasi (multi-baris)
        Route::post('formasi/update-group', [\App\Http\Controllers\FormasiJabatanController::class, 'updateGroup'])
        ->name('formasi.update-group');

        // Hapus formasi per unit & tahun
        Route::delete('formasi/delete-group', [\App\Http\Controllers\FormasiJabatanController::class, 'deleteGroup'])
        ->middleware('permission:delete formasi')
        ->name('formasi.delete-group');


    // AJAX untuk dropdown cari SDM + mini detail
    Route::get('uji/sdm-search', [UjiKompetensiController::class, 'sdmSearch'])
        ->name('uji.sdm-search'); // ?q= kata-kunci
    Route::get('uji/sdm-mini/{sdm}', [UjiKompetensiController::class, 'sdmMini'])
        ->name('uji.sdm-mini'); // detail 1 SDM
         Route::resource('uji', UjiKompetensiController::class)->names('uji');


     Route::get('/wilayah/regencies/{province}', [WilayahController::class, 'regencies'])
         ->name('wilayah.regencies');

  Route::prefix('/dashboard')->controller(PetaDashboardController::class)->group(function () {
        route::get('/peta','index')->name('peta');
             // export endpoints (tetap GET, return file)
        Route::get('/peta/export-excel', 'exportMatrixExcel')->name('dashboard.peta.export-excel')->middleware('permission:export data');
    Route::get('/peta/export-pdf',   'exportMatrixPdf')->name('dashboard.peta.export-pdf')->middleware('permission:export data');
    });

    // Laporan Terpadu (hanya admin & super_admin)
    Route::middleware(['role:admin|super_admin'])->prefix('laporan')->as('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('export-pdf/{tab}', [LaporanController::class, 'exportPdf'])->name('export-pdf');
        Route::get('export-excel/{tab}', [LaporanController::class, 'exportExcel'])->name('export-excel');
    });

});

// Uji Kompetensi (semua role kecuali viewer)
Route::middleware(['auth'])->prefix('ujikom')->as('ujikom.')->group(function () {
    Route::get('/', [UjikomController::class, 'index'])->name('index')->middleware('permission:view ujikom');
    Route::get('/create', [UjikomController::class, 'create'])->name('create')->middleware('permission:create ujikom');
    Route::post('/', [UjikomController::class, 'store'])->name('store')->middleware('permission:create ujikom');
    Route::get('/{id}', [UjikomController::class, 'show'])->name('show')->middleware('permission:view ujikom');
    Route::get('/{id}/edit', [UjikomController::class, 'edit'])->name('edit')->middleware('permission:edit ujikom');
    Route::put('/{id}', [UjikomController::class, 'update'])->name('update')->middleware('permission:edit ujikom');
    Route::delete('/{id}', [UjikomController::class, 'destroy'])->name('destroy')->middleware('permission:delete ujikom');
    Route::post('/{id}/ajukan', [UjikomController::class, 'ajukan'])->name('ajukan')->middleware('permission:create ujikom');
    Route::post('/{id}/verifikasi', [UjikomController::class, 'verifikasi'])->name('verifikasi')->middleware('permission:verifikasi ujikom');
    Route::post('/{id}/tolak', [UjikomController::class, 'tolak'])->name('tolak')->middleware('permission:verifikasi ujikom');
    Route::get('/{id}/jadwal', [UjikomController::class, 'inputJadwal'])->name('jadwal')->middleware('permission:verifikasi ujikom');
    Route::post('/{id}/jadwal', [UjikomController::class, 'simpanJadwal'])->name('simpan-jadwal')->middleware('permission:verifikasi ujikom');
    Route::post('/{id}/konfirmasi', [UjikomController::class, 'konfirmasiSelesai'])->name('konfirmasi')->middleware('permission:verifikasi ujikom');
    Route::get('/{id}/hasil', [UjikomController::class, 'inputHasil'])->name('hasil')->middleware('permission:input hasil ujikom');
    Route::post('/{id}/hasil', [UjikomController::class, 'simpanHasil'])->name('simpan-hasil')->middleware('permission:input hasil ujikom');
    Route::get('/{id}/ba/{jenis}', [UjikomController::class, 'generateBA'])->name('ba')->middleware('permission:verifikasi ujikom');
    Route::get('/{id}/export', [UjikomController::class, 'exportPdf'])->name('export')->middleware('permission:view ujikom');
    Route::get('/pegawai-list', [UjikomController::class, 'getPegawaiList'])->name('pegawai-list')->middleware('permission:view ujikom');
});

// Pertimbangan Pengangkatan JF (Operator, Admin, Super Admin)
Route::middleware(['auth'])->prefix('pengangkatan')->as('pengangkatan.')->group(function () {
    // AJAX & Export - HARUS DIAWAL sebelum route dengan parameter {id}
    Route::get('/get-pegawai', [PengangkatanController::class, 'getPegawai'])->name('get-pegawai')->middleware('auth');
    Route::get('/pegawai-list', [PengangkatanController::class, 'getPegawaiList'])->name('pegawai-list')->middleware('auth');
    Route::post('/validasi-peserta', [PengangkatanController::class, 'validasiPeserta'])->name('validasi-peserta')->middleware('permission:create pengangkatan');

    // Route CRUD
    Route::get('/', [PengangkatanController::class, 'index'])->name('index')->middleware('permission:view pengangkatan');
    Route::get('/create', [PengangkatanController::class, 'create'])->name('create')->middleware('permission:create pengangkatan');
    Route::post('/', [PengangkatanController::class, 'store'])->name('store')->middleware('permission:create pengangkatan');
    Route::get('/{id}', [PengangkatanController::class, 'show'])->name('show')->middleware('permission:view pengangkatan');
    Route::get('/{id}/edit', [PengangkatanController::class, 'edit'])->name('edit')->middleware('permission:edit pengangkatan');
    Route::put('/{id}', [PengangkatanController::class, 'update'])->name('update')->middleware('permission:edit pengangkatan');
    Route::delete('/{id}', [PengangkatanController::class, 'destroy'])->name('destroy')->middleware('permission:delete pengangkatan');

    // Aksi workflow
    Route::post('/{id}/ajukan', [PengangkatanController::class, 'ajukan'])->name('ajukan')->middleware('permission:create pengangkatan');
    Route::post('/{id}/verifikasi', [PengangkatanController::class, 'verifikasi'])->name('verifikasi')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/tolak', [PengangkatanController::class, 'tolak'])->name('tolak')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/draft-surat', [PengangkatanController::class, 'buatDraftSurat'])->name('draft-surat')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/paraf-katim', [PengangkatanController::class, 'konfirmasiParafKatim'])->name('paraf-katim')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/paraf-kabid', [PengangkatanController::class, 'konfirmasiParafKabid'])->name('paraf-kabid')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/ttd', [PengangkatanController::class, 'konfirmasiTtd'])->name('ttd')->middleware('permission:verifikasi pengangkatan');
    Route::get('/{id}/nomor', [PengangkatanController::class, 'inputNomor'])->name('nomor')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/nomor', [PengangkatanController::class, 'simpanNomor'])->name('simpan-nomor')->middleware('permission:verifikasi pengangkatan');
    Route::post('/{id}/selesaikan', [PengangkatanController::class, 'selesaikan'])->name('selesaikan')->middleware('permission:verifikasi pengangkatan');
    Route::get('/{id}/export', [PengangkatanController::class, 'exportPdf'])->name('export')->middleware('permission:view pengangkatan');
});

