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

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '下記の申請が完了しました',
                ]
            ],
        ];

        foreach ($list as $item) {
            $id = $item['id'];
            $title = $item['title'];

            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "- <https://ssl.wf.jobcan.jp/#/requests/{$id}/|{$title}>"
                ],
            ];
        }

        $response = Http::post($slackWebhookUrl, [
            'blocks' => $blocks,
        ]);

        // $this->notify(new \App\Notifications\TestSlackNotification());
        return $response->getBody()->getContents();
    }
}
