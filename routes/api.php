<?php

use App\Http\Controllers\SslInfoController;
use App\Http\Controllers\WebpConverterController;
use Illuminate\Support\Facades\Route;

Route::post('/webp-converter/convert', [WebpConverterController::class, 'convert'])->name('api.webp.convert');

Route::post('/webp-converter/convert-files', [WebpConverterController::class, 'convertFiles'])->name('api.webp.convert-files');

Route::get('/ssl-info', [SslInfoController::class, 'list'])->name('api.ssl.list');
