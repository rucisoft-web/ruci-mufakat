<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::get('/login', fn () => redirect('/app/login'))->name('login');

// Halaman publik untuk Komisaris dan Direksi, serta masuk tamu demo (hanya lihat).
Route::get('/untuk-pimpinan', [DemoController::class, 'forLeaders'])->name('pimpinan');
Route::get('/demo/tamu', [DemoController::class, 'guest'])->middleware('throttle:20,1')->name('demo.tamu');

Route::middleware('auth')->group(function () {
    Route::get('/berkas/dokumen/{document}', [FileController::class, 'document'])->name('berkas.dokumen');
    Route::get('/berkas/bukti/{evidence}', [FileController::class, 'evidence'])->name('berkas.bukti');
});

// Instalasi lewat browser (paket siap-upload). Mati otomatis setelah selesai.
Route::get('/install', [InstallController::class, 'show']);
Route::post('/install', [InstallController::class, 'store'])->middleware('throttle:10,1');
