<?php
// One poster card. Needs: $movie (array), $isActive (bool)
$posterClasses = $isActive
    ? 'ring-2 ring-primary-container'
    : 'bg-surface-container hover:scale-105 transition-all duration-300';
?>
<div class="group flex flex-col gap-space-xs cursor-pointer">
    <div class="aspect-[2/3] w-full rounded-lg overflow-hidden relative shadow-lg <?= $posterClasses ?>">
        <img class="w-full h-full object-cover" alt="Poster for <?= e($movie['title']) ?>" src="<?= e($movie['poster']) ?>">
        <?php if ($isActive): ?>
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-surface-container-lowest via-surface-container-lowest/60 to-transparent p-space-xs">
                <span class="px-space-xs py-0.5 rounded bg-primary-container text-on-primary font-eyebrow-caps text-eyebrow-caps uppercase">Now showing</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="flex flex-col">
        <h2 class="font-body-bold text-caption-label text-on-surface truncate group-hover:text-primary transition-colors"><?= e($movie['title']) ?></h2>
        <span class="font-caption-label text-[11px] text-on-surface-variant"><?= e($movie['genre']) ?> • <?= e(format_duration($movie['duration'])) ?></span>
    </div>
</div>
