<?php

namespace App\Console\Commands;

use App\Models\SslInfo;
use App\Services\SlackService;
use App\Services\SslCheckerService;
use Illuminate\Console\Command;

class GetSslInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-ssl-info {--notify : Slack 알림을 보낼지 여부}';

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
        $domains = config('ssl_domain');
        $sslInfos = SslCheckerService::checkCertificate($domains);

        # remove database
        SslInfo::truncate();
        SslInfo::insert($sslInfos);

        # send slack notification if --notify option is set
        if ($this->option('notify')) {
            SlackService::sslInfo($sslInfos);
        }
    }
}
