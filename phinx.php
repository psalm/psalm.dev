<?php

$db_config = require __DIR__ . '/dbconfig.php';
$dsn_entries = [];

foreach (explode(';', parse_url($db_config['dsn'], PHP_URL_PATH)) as $entry) {
    [$key, $val] = explode('=', $entry, 2);
    $dsn_entries[urldecode($key)] = urldecode($val);
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => parse_url($db_config['dsn'], PHP_URL_SCHEME),
            'name' => $dsn_entries['dbname'],
            'host' => $dsn_entries['host'],
            'user' => $db_config['user'],
            'pass' => $db_config['password'],
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];
