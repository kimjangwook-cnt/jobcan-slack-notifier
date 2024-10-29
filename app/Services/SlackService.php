<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SlackService
{
    public static function completedRequest($list)
    {
        // if (env('APP_ENV') == 'dev') {
        //     return;
        // }

        $slackWebhookUrl = config('env.slack_webhook_url');

        $textList = [];

        foreach ($list as $item) {
            $id = $item['id'] ?? 'NO-ID';
            $title = $item['title'] ?? 'タイトル取得不可';
            $applicant = ($item['applicant_last_name'] ?? '') . ' ' . ($item['applicant_first_name'] ?? '');

            $textList[] = [
                'type' => 'rich_text_section',
                'elements' => [
                    [
                        'type' => 'link',
                        'url' => "https://ssl.wf.jobcan.jp/#/requests/{$id}/",
                        'text' => $title,
                    ],
                    [
                        'type' => 'text',
                        'text' => ": ({$applicant})",
                    ],
                ],
            ];
        }

        $blocks = [
            [
                'type' => 'rich_text',
                'elements' => [
                    [
                        'text' => '下記の申請が完了しました',
                    ],
                ],
            ],
            [
                'type' => 'rich_text_list',
                'style' => 'bullet',
                'elements' => $textList,
            ]
        ];

        try {
            $response = Http::post($slackWebhookUrl, [
                'blocks' => $blocks,
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error("Slack通知失敗: " . $e->getMessage());
        }

        return '';
    }
}
