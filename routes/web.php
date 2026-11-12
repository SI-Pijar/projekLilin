<?php


use App\Http\Controllers\BerandaController;
use App\Http\Controllers\InformasiController;
use Illuminate\Support\Facades\Route;


Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/bantuan', [InformasiController::class, 'bantuan'])->name('bantuan');
Route::get('/syarat', [InformasiController::class, 'syarat'])->name('syarat');