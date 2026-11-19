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
    'base_url' => '/CinemaProject',
    'site_name' => 'Lumen Cinema',

    // Scheme + host only, no trailing slash. Emails are read outside the browser,
    // so links and images in them need the full address, not just the path.
    // base_url is the path part; together they make an absolute URL.
    'site_url' => 'http://localhost',

    // Ticket confirmation email.
    //   driver 'file' -> write the finished message to spool_dir as a .eml file and
    //                    read it with mailbox.php. Nothing leaves the machine.
    //   driver 'api'  -> post it to Mailtrap over HTTPS (src/MailtrapApiClient.php).
    //                    USE THIS ONE: this network blocks outbound SMTP but not
    //                    HTTPS. Fill in the api block below.
    //   driver 'smtp' -> talk to a mail server ourselves over a socket
    //                    (src/SmtpClient.php). Built and tested, but it cannot
    //                    get out of this network - the connection opens, then
    //                    EHLO never gets a reply. Same against Gmail, so it is
    //                    the network blocking SMTP, not Mailtrap.
    //   driver 'mail' -> hand the message to PHP mail(). Only works where sendmail
    //                    is set up - that means the live server, not XAMPP on a Mac.
    'mail' => [
        'driver'     => 'api',
        'from_email' => 'noreply@lumencinema.test',
        'from_name'  => 'Lumen Cinema',
        'spool_dir'  => __DIR__ . '/../storage/mail',

        // Mailtrap sandbox: a fake inbox for development. Mail sent to it is
        // caught and shown on mailtrap.io instead of being delivered, so a test
        // booking can never email a real person by accident.
        //
        // For the 'api' driver: mailtrap.io -> Email Testing -> your inbox ->
        // Integrations -> choose "API" (not SMTP). That page shows an Api-Token
        // and a send URL ending in your inbox id. Put the token in 'token' and
        // replace INBOX_ID in the endpoint with that number.
        // --- SANDBOX (current) -------------------------------------------
        // Catches everything, delivers nothing. Every test customer works.
        // Read the mail at https://mailtrap.io/inboxes
        'api' => [
            // Inbox 5039254 = "My Sandbox" on the Clifton Benjamin account.
            'endpoint' => 'https://sandbox.api.mailtrap.io/api/send/5039254',

            // Deliberately empty here. The real token lives in config/local.php,
            // which is git-ignored, so it never reaches GitHub. Copy
            // config/local.example.php to config/local.php and fill it in.
            'token'    => '',
            'timeout'  => 20,
        ],

        // --- LIVE (real delivery) ----------------------------------------
        // To really deliver to an inbox, swap the api block above for this one
        // AND change from_email to the demo-domain address, because Mailtrap
        // only accepts a sender on a domain you have set up.
        //
        //   'from_email' => 'hello@<your-demo-domain>',
        //   'api' => [
        //       'endpoint' => 'https://send.api.mailtrap.io/api/send',
        //       'token'    => '<the Email Sending token>',
        //       'timeout'  => 20,
        //   ],
        //
        // Important limit: the free demo domain only delivers to the address
        // the Mailtrap account was registered with (clifton2405@gmail.com).
        // Bookings by the seeded demo customers (alice@example.test etc.) will
        // be refused - the booking still succeeds and the ticket still shows on
        // screen, you just get the "could not email" notice. Book as customer 4
        // (clifton2405@gmail.com) to get a real email.

        // For the 'smtp' driver (same inbox, Integrations -> "SMTP"): the username
        // and password shown there belong to the inbox, not to your account.
        // Blocked on this network - see the driver notes above.
        'smtp' => [
            'host'        => 'sandbox.smtp.mailtrap.io',
            'port'        => 2525,
            'username'    => '',   // <- paste the Mailtrap inbox username
            'password'    => '',   // <- paste the Mailtrap inbox password
            'encryption'  => 'tls',
            'timeout'     => 20,
            'client_name' => 'localhost',
            'verify_peer' => true,
        ],
    ],

    // Secret used to derive the printed ticket code from a booking id.
    // Change this on the live server.
    'ticket_secret' => 'change-me-on-the-live-server',

    // Database connection (used by src/Database.php from build step 2 onward).
    'db' => [
        'host' => '127.0.0.1', // 127.0.0.1 forces TCP; avoids XAMPP socket issues
        'port' => 3306,
        'name' => 'cinema',
        'user' => 'root',
        'password' => '',
    ],
];

// --- Local overrides -------------------------------------------------------
// config/local.php holds anything that must NOT be committed (API tokens, the
// database password on a real server) or that differs per machine (base_url,
// site_url). It is listed in .gitignore, so each of us keeps our own copy and
// nothing secret ends up on GitHub.
//
// It returns an array with the same shape as $config, and only the keys it
// actually sets are replaced - array_replace_recursive merges nested arrays, so
// local.php can set just mail.api.token without repeating the rest of the block.
//
// The merge happens here, before the constants below, so local.php can override
// base_url and site_url as well.
$localConfig = __DIR__ . '/local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

// Expose site settings to the templates as constants. Guarded with defined() so
// this file can be required more than once (bootstrap + Database) without the
// "constant already defined" warning.
if (!defined('BASE_URL')) {
    define('BASE_URL', $config['base_url']);
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', $config['site_name']);
}
if (!defined('SITE_URL')) {
    define('SITE_URL', $config['site_url']);
}

// TEMPORARY: login/registration is not built yet, but a Booking needs a customer_id.
// Bookings are attributed to this demo customer for now. When real login exists,
// replace reads of this constant with the logged-in user's id.
if (!defined('GUEST_CUSTOMER_ID')) {
    define('GUEST_CUSTOMER_ID', 1);
}

// Legacy DB constants kept for any older code; new code uses $config['db'].
if (!defined('DB_HOST')) {
    define('DB_HOST', $config['db']['host']);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $config['db']['name']);
}
if (!defined('DB_USER')) {
    define('DB_USER', $config['db']['user']);
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $config['db']['password']);
}

return $config;
