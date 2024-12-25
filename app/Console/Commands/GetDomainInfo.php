<?php

namespace App\Console\Commands;

use App\Models\DomainInfo;
use App\Services\DomainCheckerService;
use App\Services\NotionReaderService;
use App\Services\SlackService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Yasumi\Yasumi;

class GetDomainInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-domain-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ドメイン情報を取得します。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyInfo = NotionReaderService::readCompanyInfo();
        $domainInfo = NotionReaderService::readDomainInfo($companyInfo);
        $domainInfos = DomainCheckerService::check($domainInfo);
        NotionReaderService::updateDomainInfo($domainInfos);

        $domainInfos = collect($domainInfos)->map(function ($domainInfo) {
            unset($domainInfo['page_id']);
            return $domainInfo;
        })->toArray();

        # remove database
        DomainInfo::truncate();
        DomainInfo::insert($domainInfos);


        $today = Carbon::now();
        $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

        $isHoliday = $holidays->isHoliday($today);
        $isWednesday = $today->dayOfWeek === Carbon::WEDNESDAY;
        $shouldNotify = !$isHoliday && $isWednesday;

        # send slack notification if --notify option is set
        if ($shouldNotify) {
            Log::info('Domain情報をSlackに通知します');
            SlackService::domainInfo($domainInfos);
        } else {
            Log::info('Domain情報をSlackに通知しません', [
                'today' => $today->format('Y-m-d'),
                'isHoliday' => $isHoliday ? '○' : '×',
                'isWednesday' => $isWednesday ? '○' : '×',
                'shouldNotify' => $shouldNotify ? '○' : '×',
            ]);
        }
    }
}
