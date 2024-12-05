<?php

use App\Http\Controllers\WebpConverterController;
use Illuminate\Support\Facades\Route;

Route::post('/webp-converter/convert', [WebpConverterController::class, 'convert'])->name('api.webp.convert');

Route::post('/webp-converter/convert-one', [WebpConverterController::class, 'convertOne'])->name('api.webp.convert-one');
