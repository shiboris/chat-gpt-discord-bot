<?php
declare(strict_types=1);

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/config/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/config/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'local',
        'local' => [
            'adapter' => 'sqlite',
            'host' => 'localhost',
            'name' => 'chat_gpt',
            'user' => '',
            'pass' => '',
            'port' => '',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation',
];
