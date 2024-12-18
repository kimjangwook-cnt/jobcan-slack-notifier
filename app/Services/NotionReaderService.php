<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class NotionReaderService
{
    public static function readCompanyInfo(): array
    {
        try {
            $apiKey = config('env.notion_api_key');
            $databaseId = config('env.company_info_notion_database_id');

            $client = new Client([
                'base_uri' => 'https://api.notion.com/v1/',
            ]);

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ];

            $results = [];
            $hasMore = true;
            $nextCursor = null;
            $prevCursor = null;

            while ($hasMore) {
                $body = [];

                if ($nextCursor) {
                    $body['start_cursor'] = $nextCursor;
                }

                $response = $client->post("databases/{$databaseId}/query", [
                    'headers' => $headers,
                    'json' => (object)$body
                ]);

                $data = json_decode($response->getBody(), true);

                // $results = array_merge($results, $data['results']);

                foreach ($data['results'] as $page) {
                    $results[] = [
                        'id' => $page['id'],
                        'site_name' => $page['properties']['企業名']['title'][0]['text']['content'] ?? '',
                    ];
                }

                $hasMore = $data['has_more'] ?? false;
                $prevCursor = $nextCursor;
                $nextCursor = $data['next_cursor'] ?? null;

                // 前のカーソルと現在のカーソルが同じ場合、無限ループを防ぐために終了
                if ($prevCursor === $nextCursor) {
                    Log::warning('Notion API: 同じnext_cursorが返されたためループを終了します。');
                    break;
                }
            }

            $results = collect($results)->unique()->toArray();

            return $results;
        } catch (GuzzleException $e) {
            throw new \Exception("Notion API 요청 실패: " . $e->getMessage());
        }
    }

    public static function updateDomainInfo($domainList = []): void
    {
        try {
            $apiKey = config('env.notion_api_key');
            $databaseId = config('env.ssl_info_notion_database_id');

            $client = new Client([
                'base_uri' => 'https://api.notion.com/v1/',
            ]);

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ];

            foreach ($domainList as $domain) {
                $properties = [];
                if ($domain['valid_to'] !== 'ー') {
                    $properties['満了日時'] = [
                        'date' => [
                            'start' => $domain['valid_to'],
                        ],
                    ];
                }
                if ($domain['days_left'] !== 'ー') {
                    $properties['期限切れまで'] = [
                        'number' => $domain['days_left'],
                    ];
                }
                if ($domain['updated_at'] !== 'ー') {
                    $properties['最終更新日時'] = [
                        'date' => [
                            'start' => $domain['updated_at'],
                        ],
                    ];
                }

                $client->patch("pages/{$domain['page_id']}", [
                    'headers' => $headers,
                    'json' => (object)[
                        'properties' => $properties,
                    ],
                ]);
            }
        } catch (GuzzleException $e) {
            throw new \Exception("Notion API 요청 실패: " . $e->getMessage());
        }
    }

    public static function readDomainInfo($companyList = []): array
    {
        try {
            $apiKey = config('env.notion_api_key');
            $databaseId = config('env.ssl_info_notion_database_id');

            $client = new Client([
                'base_uri' => 'https://api.notion.com/v1/',
            ]);

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ];

            $results = [];
            $hasMore = true;
            $nextCursor = null;
            $prevCursor = null;

            while ($hasMore) {
                $body = [];
                if ($nextCursor) {
                    $body['start_cursor'] = $nextCursor;
                }

                $response = $client->post("databases/{$databaseId}/query", [
                    'headers' => $headers,
                    'json' => (object)$body
                ]);

                $data = json_decode($response->getBody(), true);

                foreach ($data['results'] as $page) {
                    $companyId = $page['properties']['既存企業DB']['relation'][0]['id'] ?? '';
                    $companyName = collect($companyList)->firstWhere('id', $companyId)['site_name'] ?? '';

                    $results[] = [
                        'page_id' => $page['id'],
                        'domain' => $page['properties']['対象ドメイン']['rich_text'][0]['text']['content'] ?? 'ー',
                        'site_name' => $page['properties']['サイト名']['title'][0]['text']['content'] ?? 'ー',
                        'company_name' => $companyName,
                    ];
                }

                $hasMore = $data['has_more'] ?? false;
                $prevCursor = $nextCursor;
                $nextCursor = $data['next_cursor'] ?? null;

                // 前のカーソルと現在のカーソルが同じ場合、無限ループを防ぐために終了
                if ($prevCursor === $nextCursor) {
                    Log::warning('Notion API: 同じnext_cursorが返されたためループを終了します。');
                    break;
                }
            }

            return $results;
        } catch (GuzzleException $e) {
            throw new \Exception("Notion API リクエストに失敗しました: " . $e->getMessage());
        }
    }
}
