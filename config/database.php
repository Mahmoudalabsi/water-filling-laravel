<?php

use Illuminate\Support\Str;

// If DATABASE_URL is set, auto-detect driver from it.
$dbUrl = env('DATABASE_URL');
$defaultConnection = env('DB_CONNECTION', $dbUrl ? 'pgsql' : 'sqlite');

if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    if (isset($parsed['scheme'])) {
        $defaultConnection = $parsed['scheme'] === 'postgres' ? 'pgsql' : $parsed['scheme'];
    }
}

// SQLite path: prefer /data/database.sqlite (Render persistent disk)
// If not available, fall back to local storage
$sqlitePath = file_exists('/data') ? '/data/database.sqlite' : database_path('database.sqlite');

return [

    'default' => $defaultConnection,

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', $sqlitePath),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL', env('DATABASE_URL')),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => (function () use ($dbUrl) {
            $cfg = [
                'driver' => 'pgsql',
                'url' => env('DB_URL', env('DATABASE_URL')),
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => env('DB_SSLMODE', 'prefer'),
            ];
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                $cfg['host'] = $parsed['host'] ?? $cfg['host'];
                $cfg['port'] = $parsed['port'] ?? $cfg['port'];
                $cfg['database'] = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $cfg['database'];
                $cfg['username'] = $parsed['user'] ?? $cfg['username'];
                $cfg['password'] = $parsed['pass'] ?? $cfg['password'];
            }
            return $cfg;
        })(),
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];
