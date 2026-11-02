<?php

return [
    /*
     * Current Latest Distributed Client Version
     */
    'latest_client_version' => env('HBOS_LATEST_CLIENT_VERSION', '1.0.0'),

    /*
     * Minimum Supported Distributed Client Version (Clients below this receive HTTP 426 Upgrade Required)
     */
    'min_supported_client_version' => env('HBOS_MIN_SUPPORTED_CLIENT_VERSION', '1.0.0'),

    /*
     * API Version Identity
     */
    'api_version' => 'v1',

    /*
     * Sync Engine Protocol / Schema Version
     */
    'sync_schema_version' => 1,

    /*
     * Public Download / Store Update URL
     */
    'update_url' => env('HBOS_UPDATE_URL', 'https://skyrasoft.com/hbos/download'),
];
