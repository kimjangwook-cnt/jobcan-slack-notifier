<?php

use App\Services\JobCanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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
    ->everyFiveMinutes()
    ->between('09:30', '19:30')
    ->skip(function () {
        $today = Carbon::now();
        $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

        return $holidays->isHoliday($today);
    })
;

Schedule::call(function () {
    try {
        $output = Artisan::call('db:backup');
        Log::info('Database backup completed successfully', ['output' => $output]);
    } catch (\Exception $e) {
        Log::error('Database backup failed', ['error' => $e->getMessage()]);
    }
})
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->dailyAt('01:00')
    ->name('database-backup');
