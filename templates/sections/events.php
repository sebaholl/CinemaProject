<?php
// News / events cards. Needs: $events
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl">
    <div class="flex flex-col gap-space-lg">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Events, Q&amp;As &amp; Festivals</h2>
            <a class="hidden md:flex items-center gap-1 font-body-bold text-caption-label text-primary hover:underline" href="<?= e(url('news.php')) ?>">
                See all events
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
            <?php foreach ($events as $event): ?>
                <?php include __DIR__ . '/../partials/event_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
