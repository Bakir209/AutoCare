<?php
// Copy this file to config.php and fill in real values.
// config.php is listed in .gitignore and must never be committed.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'autocare',
        'user' => 'your_db_user',
        'pass' => 'your_db_password',
    ],

    // Origin of the frontend that may call this API ('*' allows any origin; fine for development).
    'cors_origin' => '*',

    // NHTSA public APIs. They need no API key; if you switch to a keyed API, put the key here.
    'nhtsa' => [
        'vpic_url'    => 'https://vpic.nhtsa.dot.gov/api',
        'recalls_url' => 'https://api.nhtsa.gov',
        'timeout'     => 5,   // seconds, applies to connect and total time
    ],

    'jwt_secret' => 'change-me-to-a-long-random-string',   // used from Milestone 4
];
