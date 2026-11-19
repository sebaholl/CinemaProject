<?php
// TEMPLATE. Copy this file to config/local.php and fill in your own values:
//
//     cp config/local.example.php config/local.php
//
// config/local.php is git-ignored, so secrets stay off GitHub and each of us can
// point the project at our own database, URL and Mailtrap inbox.
//
// Only the keys you actually set are replaced - delete the blocks you do not
// need. Nested arrays are merged, so 'token' on its own is enough.

return [
    // Mailtrap: mailtrap.io -> Email Testing -> your inbox -> Integrations -> API.
    // Put the inbox id in the endpoint and the Api-Token in 'token'.
    'mail' => [
        'api' => [
            // 'endpoint' => 'https://sandbox.api.mailtrap.io/api/send/YOUR_INBOX_ID',
            'token' => 'YOUR_MAILTRAP_API_TOKEN',
        ],
    ],

    // Where the project sits on your machine. This is the setting that keeps
    // changing between branches - set it here once and stop editing config.php.
    // 'base_url' => '/CinemaProject',
    // 'site_url' => 'http://localhost',

    // Only needed if your MySQL differs from the defaults in config.php.
    // 'db' => [
    //     'user'     => 'root',
    //     'password' => '',
    // ],
];
