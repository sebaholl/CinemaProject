<?php
// Colour key for the showtime matrix. Matches the thresholds in seat_status().
$legend = [
    ['bg-tertiary-container',  'Available seats 20–100%'],
    ['bg-secondary-container', 'Available seats 10–20%'],
    ['bg-secondary',           'Available seats 1–10%'],
    ['bg-surface-container',   'Sold out'],
];
?>
<div class="w-full pt-space-md border-t border-surface-container-highest/30 flex flex-wrap items-center justify-start gap-x-space-xl gap-y-space-sm text-on-surface-variant font-caption-label text-caption-label">
    <?php foreach ($legend as [$colorClass, $label]): ?>
        <div class="flex items-center gap-2">
            <span class="w-3.5 h-3.5 rounded <?= $colorClass ?>"></span>
            <span><?= e($label) ?></span>
        </div>
    <?php endforeach; ?>
    <div class="ml-auto text-on-surface-variant/80 italic hidden lg:inline">
        Click on a screening to open the seating plan and select seats
    </div>
</div>
