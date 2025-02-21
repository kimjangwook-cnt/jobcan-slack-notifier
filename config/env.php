<?php

$jobcanEstimateForm = [
    28184403, // 0314_見積兼受注申請
    89719946, // 0314_見積兼受注申請（緊急用）
    9842940, // 0314_見積申請（コンペ用）
];

$jobcanCostForm = [
    94613941, // 1061_その他費用申請（PJに紐づく支出）
    46444982, // 1061_その他費用申請
    75676737, // 1061_その他費用申請(部長用)（PJに紐づく支出）
    53576765, // 1061_その他費用申請(部長用)
];

$jobcanContractForm = [
    37364208, // 0312_基本契約・NDA締結申請
    34928358, // 0412_営業外の契約
];

return [
    'jobcan_api_key' => env('JOBCAN_API_KEY'),
    'jobcan_estimate_slack_url' => 'https://hooks.slack.com/services/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',
    'jobcan_estimate_form' => $jobcanEstimateForm,
    'jobcan_cost_slack_url' => 'https://hooks.slack.com/services/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',
    'jobcan_cost_form' => $jobcanCostForm,
    'jobcan_contract_slack_url' => 'https://hooks.slack.com/services/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',
    'jobcan_contract_form' => $jobcanContractForm,
    'jobcan_form' => [
        ...$jobcanEstimateForm,
        ...$jobcanCostForm,
        ...$jobcanContractForm,
    ],
    'slack_webhook_url' => env('SLACK_WEBHOOK_URL'),
    'ssl_webhook_url' => env('SSL_WEBHOOK_URL'),
    'admin_key' => env('ADMIN_KEY'),
    'notion_api_key' => env('NOTION_API_KEY'),
    'company_info_notion_database_id' => env('COMPANY_INFO_NOTION_DATABASE_ID'),
    'ssl_info_notion_database_id' => env('SSL_INFO_NOTION_DATABASE_ID'),
    'domain_info_notion_database_id' => env('DOMAIN_INFO_NOTION_DATABASE_ID'),
    'cms_ssl_info_notion_database_id' => env('CMS_SSL_INFO_NOTION_DATABASE_ID'),
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
