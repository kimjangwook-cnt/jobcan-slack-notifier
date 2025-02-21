<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SlackService
{
    public static function completedRequest($list)
    {
        $estimateFormIds = config('env.jobcan_estimate_form');
        $costFormIds = config('env.jobcan_cost_form');
        $contractFormIds = config('env.jobcan_contract_form');

        $estimateList = collect($list)->filter(function ($item) use ($estimateFormIds) {
            return in_array($item['form_id'], $estimateFormIds);
        })->values()->toArray();
        $costList = collect($list)->filter(function ($item) use ($costFormIds) {
            return in_array($item['form_id'], $costFormIds);
        })->values()->toArray();
        $contractList = collect($list)->filter(function ($item) use ($contractFormIds) {
            return in_array($item['form_id'], $contractFormIds);
        })->values()->toArray();

        $textList = [];

        foreach (
            [
                'jobcan_estimate_slack_url' => $estimateList,
                'jobcan_cost_slack_url' => $costList,
                'jobcan_contract_slack_url' => $contractList,
            ] as $key => $list
        ) {
            $testFlg = env('APP_ENV') !== 'production';
            $testLabel = $testFlg ? '【TEST用】' : '';

            if ($testFlg && $key === 'jobcan_estimate_slack_url') {
                continue;
            }

            if (count($list) === 0) {
                continue;
            }

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
                                    "text" => "{$testLabel}下記の申請が完了しました\n"
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
                $url = config("env.{$key}");
                $response = Http::post($url, [
                    'blocks' => $blocks,
                ]);
                return $response->getBody()->getContents();
            } catch (\Exception $e) {
                Log::error("Slack通知失敗: " . $e->getMessage());
            }
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
            } else if ($item['days_left'] <= 30) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under30List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 60) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under60List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 90) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under90List[] = $makeListItem($label);
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

        $makeRichText2 = function ($title) {
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
                        'elements' => [
                            [
                                'type' => 'rich_text_section',
                                'elements' => [
                                    [
                                        'type' => 'text',
                                        'text' => '該当なし',
                                    ],
                                ],
                            ]
                        ],
                    ],
                ],
            ];
        };

        // if (count($under120List) > 0) {
        //     $blocks[] = $makeRichText("期限切れまで[120日]\n", $under120List);
        // } else {
        //     $blocks[] = $makeRichText2("期限切れまで[120日]\n");
        // }

        if (count($under90List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[90日]\n", $under90List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[90日]\n");
        }

        if (count($under60List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[60日]\n", $under60List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[60日]\n");
        }

        if (count($under30List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[30日]\n", $under30List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[30日]\n");
        }

        if (count($errorList) > 0) {
            $blocks[] = $makeRichText("[証明書情報取得失敗]\n", $errorList);
        }


        try {
            if (count($errorList) > 0 || count($under90List) > 0 || count($under60List) > 0 || count($under30List) > 0) {
                $response = Http::post($slackWebhookUrl, [
                    'blocks' => $blocks,
                ]);
                return $response->getBody()->getContents();
            } else {
                Log::info("Slack通知対象のSSL情報がありません");
            }
        } catch (\Exception $e) {
            Log::error("Slack通知失敗: " . $e->getMessage());
        }

        return '';
    }


    public static function domainInfo($list)
    {
        // if (env('APP_ENV') == 'dev') {
        //     return;
        // }

        $slackWebhookUrl = env('SLACK_DOMAIN_WEBHOOK_URL');

        $errorList = [];
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
            if ($item['days_left'] == 'ー') {
                $label = "[有効期限取得失敗] {$item['company_name']} - {$item['domain']}";
                $errorList[] = $makeListItem($label);
            } else if ($item['days_left'] <= 30) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under30List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 60) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under60List[] = $makeListItem($label);
            } else if ($item['days_left'] <= 90) {
                $label = "[残り{$item['days_left']}日] {$item['company_name']} - {$item['domain']}";
                $under90List[] = $makeListItem($label);
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

        $makeRichText2 = function ($title) {
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
                        'elements' => [
                            [
                                'type' => 'rich_text_section',
                                'elements' => [
                                    [
                                        'type' => 'text',
                                        'text' => '該当なし',
                                    ],
                                ],
                            ]
                        ],
                    ],
                ],
            ];
        };

        // if (count($under120List) > 0) {
        //     $blocks[] = $makeRichText("期限切れまで[120日]\n", $under120List);
        // } else {
        //     $blocks[] = $makeRichText2("期限切れまで[120日]\n");
        // }

        if (count($under90List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[90日]\n", $under90List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[90日]\n");
        }

        if (count($under60List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[60日]\n", $under60List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[60日]\n");
        }

        if (count($under30List) > 0) {
            $blocks[] = $makeRichText("期限切れまで[30日]\n", $under30List);
        } else {
            $blocks[] = $makeRichText2("期限切れまで[30日]\n");
        }

        if (count($errorList) > 0) {
            $blocks[] = $makeRichText("[有効期限取得失敗]\n", $errorList);
        }


        try {
            if (count($errorList) > 0 || count($under90List) > 0 || count($under60List) > 0 || count($under30List) > 0) {
                $response = Http::post($slackWebhookUrl, [
                    'blocks' => $blocks,
                ]);
                return $response->getBody()->getContents();
            } else {
                Log::info("Slack通知対象のドメイン情報がありません");
            }
        } catch (\Exception $e) {
            Log::error("Slack通知失敗: " . $e->getMessage());
        }

        return '';
    }
}
