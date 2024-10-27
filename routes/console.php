<?php

use App\Services\JobCanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $insertedList = JobCanService::trigger(JobCanService::COMPLETED_REQUEST);
    if (count($insertedList) > 0) {
        Log::info(count($insertedList) . '件の申請を取得しました。');
    }
})->everyMinute();
