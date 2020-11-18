<?php

declare(strict_types=1);

use Alphagov\Notifications\Client;

return [
    'job_runner' => [
        'sleep_time' => getenv('OPG_NOTIFY_STATUS_POLLER_SLEEP_TIME_SECONDS') === false
            ? 1 : (int)getenv('OPG_NOTIFY_STATUS_POLLER_SLEEP_TIME_SECONDS')
    ],
    'notify' => [
        'api_key' => getenv('OPG_NOTIFY_API_KEY') === false ?
            '8aaa7cd4-b7af-4f49-90be-88d4815ecb72' : getenv('OPG_NOTIFY_API_KEY'),
        'base_url' => getenv('OPG_NOTIFY_BASE_URL') === false ?
            Client::BASE_URL_PRODUCTION : getenv('OPG_NOTIFY_BASE_URL'),
    ],
    'sirius' => [
        'in_progress_documents_endpoint' =>
            getenv('OPG_SIRIUS_IN_PROGRESS_DOCUMENTS_ENDPOINT') ?: '/list-in-progress',
        'update_status_endpoint' => getenv('OPG_SIRIUS_UPDATE_STATUS_ENDPOINT') ?: '/update-status',
        'jwt_secret' => getenv('JWT_SECRET') === false ? 'THiSiSaSeCrEt' : getenv('JWT_SECRET'),
        'api_user_email' => getenv('API_USER_EMAIL') === false ? 'publicapi@opgtest.com' : getenv('API_USER_EMAIL')
    ],
];
