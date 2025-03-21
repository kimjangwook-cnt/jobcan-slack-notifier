<?php

namespace App\Console\Commands;

use App\Models\CmsSslInfo;
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
    protected $signature = 'app:get-ssl-info {--test=false}';

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
        $test = $this->option('test');
        if ($test) {
            $this->info('test mode');
        }

        $companyInfo = NotionReaderService::readCompanyInfo();
        $domainInfo = NotionReaderService::readSslInfo($companyInfo);
        $sslInfos = SslCheckerService::checkCertificate($domainInfo);

        if (!$test) {
            NotionReaderService::updateSslInfo($sslInfos);
        }

        $sslInfos = collect($sslInfos)->filter(function ($sslInfo) {
            return $sslInfo['auto_renewal'] == false;
        })->map(function ($sslInfo) {
            unset($sslInfo['page_id']);
            unset($sslInfo['auto_renewal']);
            return $sslInfo;
        })->values()->toArray();

        if (!$test) {
            # remove database
            SslInfo::truncate();
            SslInfo::insert($sslInfos);
        }

        $cmsSslInfos = NotionReaderService::readCmsSslInfo();
        $cmsSslInfos = collect($cmsSslInfos)->map(function ($cmsSslInfo) {
            unset($cmsSslInfo['page_id']);
            return $cmsSslInfo;
        })->toArray();

        if (!$test) {
            CmsSslInfo::truncate();
            CmsSslInfo::insert($cmsSslInfos);
        }

        if (!$test) {
            $today = Carbon::now();
            $holidays = Yasumi::create('Japan', $today->format('Y'), 'ja_JP');

            $isHoliday = $holidays->isHoliday($today);
            $isWednesday = $today->dayOfWeek === Carbon::WEDNESDAY;
            $shouldNotify = !$isHoliday && $isWednesday;

            $sslDomains = collect($sslInfos)->pluck('domain')->toArray();
            $filteredCmsSslInfos = collect($cmsSslInfos)
                ->filter(function ($info) use ($sslDomains) {
                    return !in_array($info['domain'], $sslDomains);
                })
                ->toArray();

            $allSslInfos = [
                ...$sslInfos,
                ...$filteredCmsSslInfos,
            ];


            # send slack notification if --notify option is set
            if ($shouldNotify) {
                Log::info('Domain SSL 情報をSlackに通知します');
                SlackService::sslInfo($allSslInfos);
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
}
