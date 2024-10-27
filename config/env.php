<?php

return [
    'jobcan_api_key' => env('JOBCAN_API_KEY'),
    'slack_webhook_url' => env('SLACK_WEBHOOK_URL'),
    'ip_restriction' => [
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
