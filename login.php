<?php
// Login page (stub). Real login and registration come in build step 4.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Log In';
$currentPage = 'login';

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight pb-space-sm">Log In</h1>
    <p class="text-on-surface-variant">Login and registration coming soon.</p>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
