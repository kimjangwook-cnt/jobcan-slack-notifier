<?php

return [
    'jobcan_api_key' => env('JOBCAN_API_KEY'),
    'jobcan_target_form' => [
        28184403, // 0314_見積兼受注申請
        // 89719946, // 0314_見積兼受注申請（緊急用）
        9842940, // 0314_見積申請（コンペ用）
    ],
    'slack_webhook_url' => env('SLACK_WEBHOOK_URL'),
    'ssl_webhook_url' => env('SSL_WEBHOOK_URL'),
    'admin_key' => env('ADMIN_KEY'),
    'ssl_info_notion_api_key' => env('SSL_INFO_NOTION_API_KEY'),
    'ssl_info_notion_database_id' => env('SSL_INFO_NOTION_DATABASE_ID'),
    'allowed_ips' => [
        # LOCAL
        '127.0.0.1',

        # VPN
        '3.115.94.247',

        # OFFICE
        '124.36.26.222',

        # FORGE
        '159.203.150.232',
        '45.55.124.124',
        '159.203.150.216',
    ],
];
