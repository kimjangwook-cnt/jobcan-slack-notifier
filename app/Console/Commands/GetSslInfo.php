<?php

namespace App\Console\Commands;

use App\Models\SslInfo;
use App\Services\NotionReaderService;
use App\Services\SlackService;
use App\Services\SslCheckerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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
        $isThursday = $today->dayOfWeek === Carbon::THURSDAY;
        $shouldNotify = !$isHoliday && $isThursday;

        # send slack notification if --notify option is set
        if ($shouldNotify) {
            SlackService::sslInfo($sslInfos);
        }
    }
}
