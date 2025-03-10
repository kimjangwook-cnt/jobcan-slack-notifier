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
    'jobcan_estimate_slack_url' => env('SLACK_JOBCAN_ESTIMATE'),
    'jobcan_estimate_form' => $jobcanEstimateForm,
    'jobcan_cost_slack_url' => env('SLACK_JOBCAN_COST'),
    'jobcan_cost_form' => $jobcanCostForm,
    'jobcan_contract_slack_url' => env('SLACK_JOBCAN_CONTRACT'),
    'jobcan_contract_form' => $jobcanContractForm,
    'jobcan_form' => [
        ...$jobcanEstimateForm,
        ...$jobcanCostForm,
        ...$jobcanContractForm,
    ],
    'slack_webhook_for_test' => env('SLACK_TEST'),

    'admin_key' => env('ADMIN_KEY'),
    'notion_api_key' => env('NOTION_API_KEY'),

    'company_info_notion_database_id' => '1056a5225659800f9b85eaece05d1853',

    'ssl_webhook_url' => env('SLACK_SSL'),
    'ssl_info_notion_database_id' => '15e6a522565980fba316ea78bc544d74',

    'domain_webhook_url' => env('SLACK_DOMAIN'),
    'domain_info_notion_database_id' => '1666a522565980d5aabee321bd11110f',

    'cms_ssl_info_notion_database_id' => '1666a522565980efb0c8cc9e7e1b913f',
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
        '165.227.248.218',
    ],
];
