<?php

use App\Services\JobCanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Yasumi\Yasumi;

Schedule::call(function () {
    $insertedList = JobCanService::trigger(JobCanService::COMPLETED_REQUEST);
    if (count($insertedList) > 0) {
        Log::info(count($insertedList) . '件の申請を取得しました。');
    }
})
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->everyMinute()
    ->between('09:30', '20:00')
    ->skip(function () {
        $today = Carbon::now();
        $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

        return $holidays->isHoliday($today);
    })
;
