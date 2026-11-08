<?php
// Site + database settings. On the live server, only this file should need changing.
//
// Single source of truth: the $config array returned at the bottom.
//   - The Database class (src/Database.php) reads $config['db'] to open the PDO
//     connection. It does `require config/config.php`, so this file MUST return
//     the array.
//   - The templates/helpers use the BASE_URL and SITE_NAME constants, so we also
//     expose those as define()s built from the same array.

$config = [
    // URL path where the project lives. '' if the site is at the domain root.
    'base_url'  => '/CinemaProject',
    'site_name' => 'Lumen Cinema',

    // Database connection (used by src/Database.php from build step 2 onward).
    'db' => [
        'host'     => '127.0.0.1', // 127.0.0.1 forces TCP; avoids XAMPP socket issues
        'port'     => 3306,
        'name'     => 'cinema',
        'user'     => 'root',
        'password' => '',
    ],
];

// Expose site settings to the templates as constants. Guarded with defined() so
// this file can be required more than once (bootstrap + Database) without the
// "constant already defined" warning.
if (!defined('BASE_URL'))  { define('BASE_URL',  $config['base_url']); }
if (!defined('SITE_NAME')) { define('SITE_NAME', $config['site_name']); }

// TEMPORARY: login/registration is not built yet, but a Booking needs a customer_id.
// Bookings are attributed to this demo customer for now. When real login exists,
// replace reads of this constant with the logged-in user's id.
if (!defined('GUEST_CUSTOMER_ID')) { define('GUEST_CUSTOMER_ID', 1); }

// Legacy DB constants kept for any older code; new code uses $config['db'].
if (!defined('DB_HOST')) { define('DB_HOST', $config['db']['host']); }
if (!defined('DB_NAME')) { define('DB_NAME', $config['db']['name']); }
if (!defined('DB_USER')) { define('DB_USER', $config['db']['user']); }
if (!defined('DB_PASS')) { define('DB_PASS', $config['db']['password']); }

return $config;
