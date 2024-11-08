<?php

use App\Http\Controllers\WebpConverterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/webp-converter', [WebpConverterController::class, 'index'])->name('webp.index');
Route::post('/webp-converter/convert', [WebpConverterController::class, 'convert'])->name('webp.convert');
