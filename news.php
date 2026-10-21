<?php
// News list page (stub). Will be filled from the database later.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'News';
$currentPage = 'news';
$events      = SampleData::events();

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight pb-space-lg">News</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
        <?php foreach ($events as $event): ?>
            <?php include __DIR__ . '/templates/partials/event_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
