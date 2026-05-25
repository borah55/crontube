<?php
/**
 * Application configuration. The installer writes config.php from this template.
 * Do NOT commit config.php to source control.
 */
return [
    'app' => [
        'name'     => 'Crypto Faucet',
        'debug'    => false,
        'timezone' => 'UTC',
        // Used by the installer to verify the admin password and signed values.
        'app_key'  => '__REPLACE_ME_WITH_RANDOM_64_CHARS__',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cf_faucet',
        'user' => 'cf_user',
        'pass' => 'change_me',
    ],
];
