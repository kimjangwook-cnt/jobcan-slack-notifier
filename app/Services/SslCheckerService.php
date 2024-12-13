<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SSL証明書の情報を確認するためのサービスクラス
 */
class SslCheckerService
{
    /**
     * SSL証明書の情報を確認します。
     *
     * @param string $domain 確認対象のドメイン名
     * @param int $port ポート番号（デフォルト: 443）
     * @return array SSL証明書の情報を含む配列
     *              - issued_to: 証明書の発行先
     *              - issued_by: 証明書の発行者
     *              - valid_from: 有効期間開始日時
     *              - valid_to: 有効期間終了日時
     *              - days_left: 有効期限までの残り日数
     *              - error: エラーが発生した場合のエラーメッセージ
     */
    public static function checkCertificate(array $domains): array
    {
        $results = [];
        foreach ($domains as $domain) {
            // SSL通信用のストリームコンテキストを作成
            $context = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true,
                ],
            ]);

            // 指定されたドメインとポートにSSL接続を試みる
            $client = stream_socket_client(
                "ssl://{$domain['domain']}:443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            // 接続失敗時の例外処理
            if (!$client) {
                $results[] = [
                    "success" => false,
                    "error" => "接続に失敗しました: $errstr ($errno)",
                    "company_name" => $domain["company_name"],
                    "domain" => $domain["domain"],
                    "site_name" => $domain["site_name"],
                    "issued_to" => "ー",
                    "issued_by" => "ー",
                    "valid_from" => "ー",
                    "valid_to" => "ー",
                    "days_left" => "ー",
                ];
                continue;
            }

            // SSL証明書情報を取得
            $params = stream_context_get_params($client);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            // 証明書の解析失敗時の例外処理
            if (!$cert) {
                $results[] = [
                    "success" => false,
                    "error" => "SSL証明書の解析に失敗しました。",
                    "company_name" => $domain["company_name"],
                    "domain" => $domain["domain"],
                    "site_name" => $domain["site_name"],
                    "issued_to" => "ー",
                    "issued_by" => "ー",
                    "valid_from" => "ー",
                    "valid_to" => "ー",
                    "days_left" => "ー",
                ];
                continue;
            }

            // 証明書の有効期間を日付形式に変換
            $validFrom = date('Y-m-d H:i:s', $cert['validFrom_time_t']);
            $validTo = date('Y-m-d H:i:s', $cert['validTo_time_t']);
            // 有効期限までの残り日数を計算
            $daysLeft = (strtotime($validTo) - time()) / 86400;

            // 証明書情報を配列で返却
            $results[] = [
                "success" => true,
                "error" => null,
                "company_name" => $domain["company_name"],
                "domain" => $domain["domain"],
                "site_name" => $domain["site_name"],
                "issued_to" => $cert['subject']['CN'] ?? 'N/A',
                "issued_by" => $cert['issuer']['CN'] ?? 'N/A',
                "valid_from" => $validFrom,
                "valid_to" => $validTo,
                "days_left" => floor($daysLeft),
                "created_at" => Carbon::now()->format('Y-m-d H:i:s'),
                "updated_at" => Carbon::now()->format('Y-m-d H:i:s'),
            ];
        }

        return $results;
    }
}
