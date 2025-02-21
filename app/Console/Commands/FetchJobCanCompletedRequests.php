<?php

namespace App\Console\Commands;

use App\Services\JobCanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchJobCanCompletedRequests extends Command
{
    protected $signature = 'jobcan:fetch-completed-requests
                          {--period= : 期間(日)}';
    protected $description = 'Fetch completed requests from JobCan';

    public function handle()
    {
        $period = $this->option('period') ?? 10;

        $insertedList = JobCanService::trigger(
            JobCanService::COMPLETED_REQUEST,
            ['period' => 60 * 24 * $period]
        );

        if (count($insertedList) > 0) {
            Log::info(count($insertedList) . '件の申請を取得しました。');
            $this->info(count($insertedList) . '件の申請を取得しました。');
        }

        return Command::SUCCESS;
    }
}
