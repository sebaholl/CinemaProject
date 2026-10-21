<?php
// Page layout: top part. Expects $pageTitle and $currentPage to be set by the page.
$navItems = [
    'home'    => ['Home',    'index.php'],
    'movies'  => ['Movies',  'movies.php'],
    'news'    => ['News',    'news.php'],
    'contact' => ['Contact', 'contact.php'],
];
$navActive = 'transition-colors bg-primary-container text-on-primary font-body-bold px-space-md py-space-xs rounded-full';
$navIdle   = 'font-body-regular text-body-regular text-on-surface-variant hover:text-on-surface transition-colors py-space-xs px-space-sm';
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= e(asset('js/tailwind-config.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body class="bg-surface-container-lowest font-body-regular text-body-regular text-on-surface antialiased">

<header class="fixed top-0 left-0 right-0 w-full z-50 bg-surface-container-lowest/85 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.6)]">
    <div class="h-20 w-full px-margin-mobile md:px-margin flex items-center justify-between gap-space-lg">
        <div class="flex items-center gap-space-md">
            <a href="<?= e(url('index.php')) ?>">
                <img alt="<?= e(SITE_NAME) ?> Logo" class="h-8 w-auto object-contain" src="https://lh3.googleusercontent.com/aida/AEtjO1Wgklp-IoZZWWXW6f6Y2FciAAXeJarrNmPSCRcBmjw6Fl07oApJml9_jpOYN_YBl6t2SRKnJ6epmUg6LINLtI1iPQYqsVw3RhOVFUk7eXMh3HEXr3BukCBLEEso6tz58Rx9UxyzR7jjMGBC6qIikyjvtDxRrItxS3QDUGEioamWFhF0u6nL8_Y6usT8MdPGOovItnDKoxMOXVlZDpZXhNqwV-XDQW6cNnUqLe8xHUedouiF4FZspvEGzOH1">
            </a>
        </div>

        <nav class="hidden md:flex items-center gap-space-lg">
            <?php foreach ($navItems as $key => [$label, $page]): ?>
                <a href="<?= e(url($page)) ?>"
                   class="<?= $key === $currentPage ? $navActive : $navIdle ?>"
                   <?= $key === $currentPage ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-space-md">
            <button aria-label="Search screenings and movies" class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors" type="button">
                <span class="material-symbols-outlined">search</span>
            </button>
            <div class="flex items-center gap-space-sm p-space-xs pl-space-sm bg-surface-container-low rounded-full">
                <a class="hidden md:inline-flex items-center justify-center px-space-md py-space-xs rounded-full bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors"
                   href="<?= e(url('login.php')) ?>">Log In</a>
            </div>
        </div>
    </div>
</header>

<main class="w-full pt-20 bg-surface-container-lowest min-h-screen">
