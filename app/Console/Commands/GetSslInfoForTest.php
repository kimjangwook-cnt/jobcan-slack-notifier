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

class GetSslInfoForTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-ssl-info-for-test';

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
        $domain = [
            'company_name' => '株式会社コネクティ',
            'domain' => 'connecty.co.jp',
            'site_name' => 'コネクティ',
            'page_id' => '1234567890',
        ];

        $sslInfo = SslCheckerService::checkCertificateOne($domain);
        dd($sslInfo);
    }
}
