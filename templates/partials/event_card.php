<?php
// One news/event card. Needs: $event (array)
$badgeClasses = $event['highlight']
    ? 'bg-primary-container text-on-primary'
    : 'bg-surface-container-highest text-on-surface';
?>
<article class="flex flex-col bg-surface-container-low rounded-2xl overflow-hidden shadow-md group hover:bg-surface-container transition-colors">
    <div class="aspect-[16/9] w-full relative overflow-hidden bg-surface-container">
        <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?= e($event['title']) ?>" src="<?= e($event['image']) ?>">
        <div class="absolute top-space-sm left-space-sm px-space-sm py-1 rounded-full <?= $badgeClasses ?> font-eyebrow-caps text-eyebrow-caps uppercase">
            <?= e($event['date_label']) ?>
        </div>
    </div>
    <div class="p-space-lg flex flex-col justify-between flex-1 gap-space-md">
        <div class="space-y-space-xs">
            <span class="font-caption-label text-caption-label text-secondary uppercase font-semibold"><?= e($event['category']) ?></span>
            <h3 class="font-headline-lg text-subheading text-on-surface font-bold group-hover:text-primary transition-colors"><?= e($event['title']) ?></h3>
            <p class="font-body-regular text-caption-label text-on-surface-variant leading-relaxed"><?= e($event['excerpt']) ?></p>
        </div>
        <a class="inline-flex items-center gap-1 font-body-bold text-caption-label text-on-surface group-hover:text-primary transition-colors pt-space-xs"
           href="<?= e(url('news.php?id=' . (int) $event['id'])) ?>">
            <?= e($event['link_text']) ?>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>
</article>
