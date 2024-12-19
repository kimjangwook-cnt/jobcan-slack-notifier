<?php

namespace App\Console\Commands;

use App\Models\SslInfo;
use App\Services\NotionReaderService;
use App\Services\SlackService;
use App\Services\SslCheckerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Yasumi\Yasumi;

class GetSslInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-ssl-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SSL証明書の情報を取得します。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyInfo = NotionReaderService::readCompanyInfo();
        $domainInfo = NotionReaderService::readDomainInfo($companyInfo);
        $sslInfos = SslCheckerService::checkCertificate($domainInfo);
        NotionReaderService::updateDomainInfo($sslInfos);

        $sslInfos = collect($sslInfos)->map(function ($sslInfo) {
            unset($sslInfo['page_id']);
            return $sslInfo;
        })->toArray();

        # remove database
        SslInfo::truncate();
        SslInfo::insert($sslInfos);


        $today = Carbon::now();
        $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

        $isHoliday = $holidays->isHoliday($today);
        $isWednesday = $today->dayOfWeek === Carbon::WEDNESDAY;
        $shouldNotify = !$isHoliday && $isWednesday;

        # send slack notification if --notify option is set
        if ($shouldNotify) {
            Log::info('Domain SSL 情報をSlackに通知します');
            SlackService::sslInfo($sslInfos);
        } else {
            Log::info('Domain SSL 情報をSlackに通知しません', [
                'today' => $today->format('Y-m-d'),
                'isHoliday' => $isHoliday ? '○' : '×',
                'isWednesday' => $isWednesday ? '○' : '×',
                'shouldNotify' => $shouldNotify ? '○' : '×',
            ]);
        }
    }
}
