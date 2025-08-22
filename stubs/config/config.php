<?php

return [
    'app' => [
        'name' => 'My WordPress Plugin',
        'version' => '1.0.0',
        'description' => 'A WordPress plugin built with ADZ Framework',
    ],
    
    'database' => [
        'prefix' => 'adz_',
        'charset' => 'utf8mb4',
        'collate' => 'utf8mb4_unicode_ci',
    ],
    
    'assets' => [
        'version' => '1.0.0',
        'minify' => true,
    ],
    
    'security' => [
        'nonce_action' => 'adz_security_nonce',
        'capability' => 'manage_options',
    ],
];