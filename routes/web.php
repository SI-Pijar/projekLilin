<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\InformasiController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TantanganController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BerandaController::class, 'index'])->name('beranda');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'create'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:masuk')->name('masuk.store');
    Route::get('/daftar', [AuthController::class, 'daftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'simpanDaftar'])->middleware('throttle:daftar')->name('daftar.store');
});

Route::get('/syarat', [InformasiController::class, 'syarat'])->name('syarat');
Route::get('/bantuan', [InformasiController::class, 'bantuan'])->name('bantuan');

Route::middleware(['auth', 'peran:member,mentor,admin'])->group(function () {
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/sandi', [ProfilController::class, 'sandi'])->name('profil.sandi');
});

Route::post('/keluar', [AuthController::class, 'destroy'])->middleware('auth')->name('keluar');

Route::middleware(['auth', 'peran:member'])->group(function () {
    Route::get('/analisis', [AnalisisController::class, 'create'])->name('analisis.create');
    Route::post('/analisis', [AnalisisController::class, 'store'])->middleware('throttle:analisis')->name('analisis.store');
    Route::get('/analisis/{analisis}', [AnalisisController::class, 'show'])->name('analisis.show');
    Route::post('/analisis/{analisis}/ulang', [AnalisisController::class, 'ulang'])->middleware('throttle:analisis')->name('analisis.ulang');
    Route::put('/analisis/{analisis}', [AnalisisController::class, 'update'])->name('analisis.update');
    Route::delete('/analisis/{analisis}', [AnalisisController::class, 'destroy'])->name('analisis.destroy');
    Route::get('/riwayat', [AnalisisController::class, 'index'])->name('analisis.index');
    Route::get('/saham/{kode}', [AnalisisController::class, 'saham'])->whereAlpha('kode')->name('analisis.saham');

    Route::post('/analisis/{analisis}/review', [ReviewController::class, 'store'])->name('review.store');
    Route::post('/review/{review}/setuju', [ReviewController::class, 'setuju'])->name('review.setuju');
    Route::post('/review/{review}/batal', [ReviewController::class, 'batal'])->name('review.batal');

    Route::get('/tantangan', [TantanganController::class, 'index'])->name('tantangan.index');
    Route::post('/tantangan/{tantangan}/jawab', [TantanganController::class, 'jawab'])->name('tantangan.jawab');

    Route::get('/paket', [PaketController::class, 'index'])->name('paket.index');
    Route::post('/paket', [PaketController::class, 'store'])->name('paket.store');
    Route::put('/paket/{pembayaran}', [PaketController::class, 'update'])->name('paket.update');
    Route::delete('/paket/{pembayaran}', [PaketController::class, 'destroy'])->name('paket.destroy');
});



Route::middleware(['auth', 'peran:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/', [MentorController::class, 'index'])->name('index');
    Route::put('/review/{review}', [MentorController::class, 'kirimReview'])->name('review.update');
    Route::get('/tantangan', [MentorController::class, 'tantangan'])->name('tantangan');
    Route::post('/tantangan', [MentorController::class, 'simpanTantangan'])->name('tantangan.store');
    Route::get('/tantangan/{tantangan}/edit', [MentorController::class, 'editTantangan'])->name('tantangan.edit');
    Route::put('/tantangan/{tantangan}', [MentorController::class, 'updateTantangan'])->name('tantangan.update');
    Route::delete('/tantangan/{tantangan}', [MentorController::class, 'hapusTantangan'])->name('tantangan.destroy');
});

Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::put('/pembayaran/{pembayaran}', [AdminController::class, 'periksaPembayaran'])->name('pembayaran.update');
    Route::put('/pembayaran/{pembayaran}/cabut', [AdminController::class, 'cabutPembayaran'])->name('pembayaran.cabut');
    Route::get('/pembayaran/{pembayaran}/bukti', [AdminController::class, 'buktiPembayaran'])->name('pembayaran.bukti');
    Route::put('/tantangan/{tantangan}', [AdminController::class, 'periksaTantangan'])->name('tantangan.update');
    Route::put('/review/{review}/pindah', [AdminController::class, 'pindahkanReview'])->name('review.pindah');


    Route::get('/pengguna', [AdminController::class, 'pengguna'])->name('pengguna');
    Route::put('/pengguna/{user}', [AdminController::class, 'ubahPengguna'])->name('pengguna.update');
    Route::put('/pengguna/{user}/sandi', [AdminController::class, 'setelSandi'])->middleware('throttle:20,1')->name('pengguna.sandi');
});

Route::middleware(['auth', 'peran:member,mentor,admin'])->get('/tantangan/{tantangan}/gambar/{jenis}', [TantanganController::class, 'gambar'])
    ->whereIn('jenis', ['potong', 'lanjutan'])->name('tantangan.gambar');
