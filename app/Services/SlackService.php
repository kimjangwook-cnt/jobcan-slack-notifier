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
                        "type" => "rich_text_section",
                        "elements" => [
                            [
                                "type" => "text",
                                "text" => "下記の申請が完了しました\n"
                            ],
                        ]
                    ],
                    [
                        'type' => 'rich_text_list',
                        'style' => 'bullet',
                        'elements' => $textList,
                    ],
                ],
            ],
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


    public static function sslInfo($list)
    {
        // if (env('APP_ENV') == 'dev') {
        //     return;
        // }

        $slackWebhookUrl = config('env.ssl_webhook_url');

        $errorList = [];
        $under120List = [];
        $under90List = [];
        $under60List = [];
        $under30List = [];
        $makeListItem = function ($label) {
            return [
                'type' => 'rich_text_section',
                'elements' => [
                    [
                        'type' => 'text',
                        'text' => $label,
                    ],
                ],
            ];
        };

        foreach ($list as $item) {
            if ($item['success'] == false) {
                $label = "[SSL情報取得失敗] {$item['company_name']} - {$item['domain']}: ({$item['error']})";
                $errorList[] = $makeListItem($label);
            } else if ($item['days_left'] <= 120) {
                $label = "[満了まで{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                if (env('APP_ENV') !== 'production') {
                    $under120List[] = $makeListItem($label);
                }
            } else if ($item['days_left'] <= 90) {
                $label = "[満了まで{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under90List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 60) {
                $label = "[満了まで{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under60List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 30) {
                $label = "[満了まで{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under30List[] = $makeListItem($label);
            }
        }

        $blocks = [];
        $makeRichText = function ($title, $list) {
            return [
                'type' => 'rich_text',
                'elements' => [
                    [
                        "type" => "rich_text_section",
                        "elements" => [
                            [
                                "type" => "text",
                                "text" => $title,
                            ],
                        ]
                    ],
                    [
                        'type' => 'rich_text_list',
                        'style' => 'bullet',
                        'elements' => $list,
                    ],
                ],
            ];
        };

        if (count($errorList) > 0) {
            $blocks[] = $makeRichText("[証明書情報取得失敗]\n", $errorList);
        }

        if (count($under120List) > 0) {
            $blocks[] = $makeRichText("[満了まで120日]\n", $under120List);
        }

        if (count($under90List) > 0) {
            $blocks[] = $makeRichText("[満了まで90日]\n", $under90List);
        }

        if (count($under60List) > 0) {
            $blocks[] = $makeRichText("[満了まで60日]\n", $under60List);
        }

        if (count($under30List) > 0) {
            $blocks[] = $makeRichText("[満了まで30日]\n", $under30List);
        }


        try {
            if (count($errorList) > 0 || count($under120List) > 0 || count($under90List) > 0 || count($under60List) > 0 || count($under30List) > 0) {
                $response = Http::post($slackWebhookUrl, [
                    'blocks' => $blocks,
                ]);
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            Log::error("Slack通知失敗: " . $e->getMessage());
        }

        return '';
    }
}
