<?php
$adminLinks = [
    'dashboard' => ['Dashboard', 'index.php'],
    'bookings' => ['Bookings', 'bookings.php'],
    'invoices' => ['Invoices', 'invoices.php'],
    'company' => ['Company', 'company.php'],
    'hours' => ['Hours', 'hours.php'],
    'contact' => ['Contact info', 'contact.php'],
    'movies' => ['Movies', 'movies.php'],
    'showings' => ['Showings', 'showings.php'],
    'news' => ['News', 'news.php'],
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= e(asset('js/tailwind-config.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body class="bg-surface-container-lowest text-on-surface antialiased">
<header class="bg-surface-container-low border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="<?= e(url('admin/index.php')) ?>" class="text-xl font-bold">
                <?= e(SITE_NAME) ?> · Admin
            </a>
            <p class="text-sm text-on-surface-variant">
                Signed in as <?= e($adminUser['name']) ?>
            </p>
        </div>

        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button type="submit"
                    class="rounded-xl bg-primary-container px-4 py-2 text-on-primary">
                Log out
            </button>
        </form>
    </div>

    <nav aria-label="Admin navigation"
         class="max-w-7xl mx-auto px-4 pb-4 flex flex-wrap gap-2">
        <?php foreach ($adminLinks as $key => [$label, $file]): ?>
            <?php if (is_file(__DIR__ . '/../../admin/' . $file)): ?>
                <a href="<?= e(url('admin/' . $file)) ?>"
                   class="rounded-lg px-3 py-2 <?= $adminPage === $key
                       ? 'bg-primary-container text-on-primary'
                       : 'bg-surface-container-high hover:underline' ?>"
                   <?= $adminPage === $key ? 'aria-current="page"' : '' ?>>
                    <?= e($label) ?>
                </a>
            <?php else: ?>
                <span class="rounded-lg px-3 py-2 opacity-50"
                      title="This section is coming later">
                    <?= e($label) ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</header>

<main class="max-w-7xl mx-auto px-4 py-8">
    <?php if ($flash !== null): ?>
        <p role="status"
           class="mb-6 rounded-xl bg-surface-container-high p-4">
            <?= e($flash) ?>
        </p>
    <?php endif; ?>