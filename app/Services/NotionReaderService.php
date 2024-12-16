<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NotionReaderService
{
    public static function readDomainInfo(): array
    {
        try {
            $apiKey = config('env.ssl_info_notion_api_key');
            $databaseId = config('env.ssl_info_notion_database_id');

            $client = new Client([
                'base_uri' => 'https://api.notion.com/v1/',
            ]);

            $headers = [
                'Authorization' => 'Bearer ' . $apiKey,
                'Notion-Version' => '2022-06-28',
                'Content-Type' => 'application/json',
            ];

            $response = $client->post("databases/{$databaseId}/query", [
                'headers' => $headers,
                'data' => json_encode([
                    'filter' => [
                        // 'property' => 'Last ordered',
                        // 'date' => [
                        //     'past_week' => (object)[]
                        // ]
                    ]
                ])
            ]);

            $data = json_decode($response->getBody(), true);
            // return $data;
            $results = [];

            foreach ($data['results'] as $page) {
                $results[] = [
                    'company_name' => $page['properties']['会社名']['select']['name'] ?? '',
                    'domain' => $page['properties']['ドメイン']['url'] ?? '',
                    'site_name' => $page['properties']['サイト名']['title'][0]['text']['content'] ?? '',
                ];
            }

            return $results;
        } catch (GuzzleException $e) {
            throw new \Exception("Notion API 요청 실패: " . $e->getMessage());
        }
    }
}
