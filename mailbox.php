<?php
declare(strict_types=1);

// Development mailbox. Lists the emails the 'file' mail driver wrote to
// storage/mail/ and renders one of them, so we can check and demo the ticket
// email without a working mail server.
//
// Locked down three ways, because the spooled files contain customer names and
// addresses: the folder is denied by storage/.htaccess, this page 404s unless the
// driver is 'file' (i.e. never on the live server), and it requires a login.
require_once __DIR__ . '/includes/bootstrap.php';

$mailConfig = config('mail');

// On the live server the driver is 'mail', so this page does not exist at all.
if (($mailConfig['driver'] ?? '') !== 'file') {
    http_response_code(404);
    exit('Not found.');
}

$pdo  = Database::getInstance()->getConnection();
$auth = new Auth($pdo);
$auth->requireLogin();

$spool = (string) ($mailConfig['spool_dir'] ?? '');
$files = [];

if (is_dir($spool)) {
    foreach ((array) glob(rtrim($spool, '/') . '/*.eml') as $path) {
        $files[] = $path;
    }
    rsort($files); // filenames start with the timestamp, so newest first
}

// Which message to show. Only ever a basename from our own listing, so a crafted
// ?file=../../config/config.php cannot escape the spool folder.
$wanted  = isset($_GET['file']) ? basename((string) $_GET['file']) : '';
$current = null;

foreach ($files as $path) {
    if ($wanted === '' || basename($path) === $wanted) {
        $current = $path;
        break;
    }
}

// ?raw=1 serves just the HTML part, for the preview iframe below.
if ($current !== null && isset($_GET['raw'])) {
    $message = (string) file_get_contents($current);

    // Pull the base64 text/html part out of the multipart/alternative body.
    $html = null;
    if (preg_match(
        '/Content-Type:\s*text\/html.*?Content-Transfer-Encoding:\s*base64\s*\R\R(.*?)\R--/s',
        $message,
        $m
    ) === 1) {
        $html = base64_decode(preg_replace('/\s+/', '', $m[1]) ?? '', true) ?: null;
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Security-Policy: sandbox allow-popups');
    echo $html ?? '<p>Could not read an HTML part from this message.</p>';
    exit;
}

$pageTitle   = 'Dev mailbox';
$currentPage = '';
require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1100px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Dev mailbox</h1>
    <p class="mt-space-sm text-on-surface-variant">
        Emails written to <code><?= e($spool) ?></code> by the <code>file</code> mail driver.
        Switch the driver to <code>mail</code> in <code>config/config.php</code> to send for real.
    </p>

    <?php if ($files === []): ?>
        <p class="mt-space-lg text-on-surface-variant">
            No emails yet. Complete a booking and come back.
        </p>
    <?php else: ?>
        <div class="mt-space-lg flex flex-col md:flex-row gap-space-lg">

            <ul class="md:w-[280px] shrink-0 flex flex-col gap-space-xs">
                <?php foreach ($files as $path): $name = basename($path); ?>
                    <li>
                        <a href="<?= e(url('mailbox.php?file=' . rawurlencode($name))) ?>"
                           class="block px-space-md py-space-sm rounded-xl <?= $current !== null && basename($current) === $name
                               ? 'bg-primary-container text-on-primary'
                               : 'bg-surface-container-low text-on-surface hover:bg-surface-container' ?>">
                            <span class="block font-body-bold text-caption-label"><?= e($name) ?></span>
                            <span class="block font-caption-label text-caption-label opacity-80">
                                <?= e(gmdate('d.m.Y H:i', (int) filemtime($path))) ?> UTC
                                · <?= e((string) round(filesize($path) / 1024)) ?> KB
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($current !== null): ?>
                <div class="flex-1">
                    <p class="text-on-surface-variant font-caption-label text-caption-label">
                        Preview · <a class="text-primary hover:underline"
                           href="<?= e(url('mailbox.php?raw=1&file=' . rawurlencode(basename($current)))) ?>"
                           target="_blank" rel="noopener">open in a new tab</a>
                    </p>
                    <iframe title="Email preview"
                            src="<?= e(url('mailbox.php?raw=1&file=' . rawurlencode(basename($current)))) ?>"
                            class="mt-space-sm w-full h-[760px] rounded-xl border border-surface-container-highest/40 bg-white"></iframe>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
