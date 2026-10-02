<?php

return [
    'default' => env('LOG_CHANNEL', env('APP_LOG_CHANNEL', 'stack')),
    'deprecations' => ['channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'), 'trace' => false],
    'channels' => [
        'stack' => ['driver' => 'stack', 'channels' => ['single'], 'ignore_exceptions' => false],
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug')],
        'stderr' => ['driver' => 'errorlog', 'level' => env('LOG_LEVEL', 'debug')],
    ],
];
