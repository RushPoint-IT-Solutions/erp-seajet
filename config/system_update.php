<?php

return [
    'enabled' => env('SYSTEM_UPDATE_ENABLED', true),
    'remote' => env('SYSTEM_UPDATE_REMOTE', 'origin'),
    'composer_binary' => env('COMPOSER_BINARY', 'composer'),
    'composer_no_dev' => env('SYSTEM_UPDATE_NO_DEV', env('APP_ENV') === 'production'),
    'timeout' => (int) env('SYSTEM_UPDATE_TIMEOUT', 1200),
];
