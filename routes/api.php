<?php

use App\Http\Controllers\JobCanNotifyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/jobcan-notify', [JobCanNotifyController::class, 'index']);
Route::post('/jobcan-notify/forms', [JobCanNotifyController::class, 'forms']);
