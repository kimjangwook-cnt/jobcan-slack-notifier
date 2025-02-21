<?php

use App\Services\JobCanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Yasumi\Yasumi;

Schedule::command('jobcan:fetch-completed-requests')
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->everyFiveMinutes()
    ->between('09:30', '19:30')
    ->skip(function () {
        $today = Carbon::now();
        $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

        return $holidays->isHoliday($today);
    });

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

// ssl 証明書の有効期限をチェックする
Schedule::call(function () {
    try {
        Artisan::call('app:get-ssl-info');
    } catch (\Exception $e) {
        Log::error('SSL証明書の有効期限チェックに失敗しました', ['error' => $e->getMessage()]);
    }
})
    ->timezone('Asia/Tokyo')
    ->dailyAt('10:00')
    ->name('ssl-certificate-check');

// ドメインの有効期限をチェックする
Schedule::call(function () {
    try {
        Artisan::call('app:get-domain-info');
    } catch (\Exception $e) {
        Log::error('ドメインの有効期限チェックに失敗しました', ['error' => $e->getMessage()]);
    }
})
    ->timezone('Asia/Tokyo')
    ->dailyAt('10:00')
    ->name('domain-check');
