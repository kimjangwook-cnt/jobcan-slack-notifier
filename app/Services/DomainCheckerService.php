<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Iodev\Whois\Factory;
use Iodev\Whois\Whois;

/**
 * SSL証明書の情報を確認するためのサービスクラス
 */
class DomainCheckerService
{
    public static function checkOne($domain)
    {
        $whois = Factory::get()->createWhois();
        $info = $whois->loadDomainInfo($domain['domain']);

        $daysLeft = $info->expirationDate ? floor(($info->expirationDate - time()) / 86400) : 'ー';

        return [
            'page_id' => $domain['page_id'],
            'company_name' => $domain['company_name'],
            'domain' => $info->domainName ?? 'ー',
            'owner' => $info->owner ?? 'ー',
            'registrar' => $info->registrar ?? 'ー',
            'created_at' => $info->creationDate ? Carbon::parse($info->creationDate)->timezone('Asia/Tokyo')->format('Y-m-d H:i:s') : 'ー',
            'expires_at' => $info->expirationDate ? Carbon::parse($info->expirationDate)->timezone('Asia/Tokyo')->format('Y-m-d H:i:s') : 'ー',
            'updated_at' => $info->updatedDate ? Carbon::parse($info->updatedDate)->timezone('Asia/Tokyo')->format('Y-m-d H:i:s') : 'ー',
            'days_left' => $daysLeft,
        ];
    }

    public static function check(array $domains): array
    {
        $results = [];
        $updatedAt = Carbon::now()->format('Y-m-d H:i:s');
        foreach ($domains as $domain) {
            $results[] = self::checkOne($domain);
        }

        return $results;
    }
}
