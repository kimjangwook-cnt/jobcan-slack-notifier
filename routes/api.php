<?php

use App\Http\Controllers\JobCanNotifyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/jobcan-notify', [JobCanNotifyController::class, 'index']);
