<?php
// Copy this file to config.php and fill in real values.
// config.php is listed in .gitignore and must never be committed.
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'autocare',
        'user' => 'your_db_user',
        'pass' => 'your_db_password',
    ],
    'jwt_secret' => 'change-me-to-a-long-random-string',
    'nhtsa' => [
        'base_url' => 'https://vpic.nhtsa.dot.gov/api',
        'recalls_url' => 'https://api.nhtsa.gov',
        'timeout' => 5,
    ],
];
