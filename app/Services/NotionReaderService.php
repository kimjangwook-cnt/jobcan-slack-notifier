<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
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

    public static function updateSslInfo($domainList = []): void
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

                if ($domain['issued_by'] !== 'ー') {
                    $properties['証明書発行者'] = [
                        'rich_text' => [
                            [
                                'text' => [
                                    'content' => $domain['issued_by'],
                                ]
                            ]
                        ],
                    ];
                }

                Log::info("Notion API: ページID: {$domain['page_id']} のプロパティを更新します。", [
                    'domain' => $domain,
                    'properties' => $properties,
                ]);

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

    public static function readSslInfo($companyList = []): array
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

    public static function readCmsSslInfo(): array
    {
        try {
            $apiKey = config('env.notion_api_key');
            $databaseId = config('env.cms_ssl_info_notion_database_id');

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
                    Log::info(json_encode($page, JSON_PRETTY_PRINT));

                    $results[] = [
                        'page_id' => $page['id'],
                        'domain' => $page['properties']['対象ドメイン']['rich_text'][0]['text']['content'] ?? 'ー',
                        'site_name' => $page['properties']['サイト名']['title'][0]['text']['content'] ?? 'ー',
                        'success' => true,
                        'company_name' => 'ー',
                        'domain' => $page['properties']['Hostname']['title'][0]['text']['content'] ?? 'ー',
                        'site_name' => 'ー',
                        'issued_to' => null,
                        'issued_by' => $page['properties']['SSL Issuer']['rich_text'][0]['text']['content'] ?? null,
                        'valid_from' => null,
                        'valid_to' => $page['properties']['Expiry Date']['date']['start'] ?? null,
                        'days_left' => $page['properties']['Days Remaining']['number'] ?? null,
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


    public static function readDomainInfo($companyList = []): array
    {
        try {
            $apiKey = config('env.notion_api_key');
            $databaseId = config('env.domain_info_notion_database_id');

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
                        'domain' => $page['properties']['対象ドメイン']['title'][0]['text']['content'] ?? 'ー',
                        // 'site_name' => $page['properties']['サイト名']['title'][0]['text']['content'] ?? 'ー',
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


    public static function updateDomainInfo($domainList = []): void
    {
        try {
            $apiKey = config('env.notion_api_key');

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
                if ($domain['created_at'] !== 'ー') {
                    $properties['登録日時'] = [
                        'date' => [
                            'start' => $domain['created_at'],
                        ],
                    ];
                }
                if ($domain['expires_at'] !== 'ー') {
                    $properties['満了日時'] = [
                        'date' => [
                            'start' => $domain['expires_at'],
                        ],
                    ];
                }
                if ($domain['updated_at'] !== 'ー') {
                    $properties['最終更新日時'] = [
                        'date' => [
                            'start' => $domain['updated_at'],
                        ],
                    ];
                }
                if ($domain['days_left'] !== 'ー') {
                    $properties['期限切れまで'] = [
                        'number' => $domain['days_left'],
                    ];
                }


                $properties['所有者'] = [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $domain['owner'],
                            ]
                        ]
                    ],
                ];

                $properties['レジストラ'] = [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $domain['registrar'],
                            ]
                        ]
                    ],
                ];

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
}
