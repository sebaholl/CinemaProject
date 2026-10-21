<?php
// One showtime button in the schedule matrix. Needs: $slot (array)
// Colour depends on how many seats are left (see seat_status() in includes/helpers.php).
$status = seat_status($slot['seats_left'], $slot['capacity']);
$bookingUrl = url('booking.php?showing_id=' . (int) $slot['id']);
?>
<?php if ($status === 'sold_out'): ?>
    <div class="w-full p-2.5 rounded-xl bg-surface-container opacity-50 cursor-not-allowed text-center flex flex-col items-center">
        <span class="font-eyebrow-caps text-[11px] text-error font-bold">Screen <?= (int) $slot['screen'] ?></span>
        <span class="font-time-slot-headline text-time-slot-headline font-bold text-on-surface-variant line-through tracking-tight"><?= e($slot['time']) ?></span>
        <span class="font-caption-label text-[10px] text-error">Sold out</span>
    </div>

<?php elseif ($status === 'critical'): ?>
    <a href="<?= e($bookingUrl) ?>" class="w-full p-2.5 rounded-xl bg-secondary hover:brightness-110 active:scale-95 transition-all text-on-secondary text-center flex flex-col items-center">
        <span class="font-eyebrow-caps text-[11px] text-on-secondary font-bold">Screen <?= (int) $slot['screen'] ?> • <?= (int) $slot['seats_left'] ?> seats</span>
        <span class="font-time-slot-headline text-time-slot-headline font-bold text-on-secondary tracking-tight"><?= e($slot['time']) ?></span>
        <span class="font-caption-label text-[10px] text-on-secondary">Almost sold out</span>
    </a>

<?php elseif ($status === 'low'): ?>
    <a href="<?= e($bookingUrl) ?>" class="w-full p-2.5 rounded-xl bg-secondary-container hover:brightness-110 active:scale-95 transition-all text-on-surface text-center flex flex-col items-center">
        <span class="font-eyebrow-caps text-[11px] text-on-secondary-container font-bold">Screen <?= (int) $slot['screen'] ?> • Few seats</span>
        <span class="font-time-slot-headline text-time-slot-headline font-bold text-white tracking-tight"><?= e($slot['time']) ?></span>
        <span class="font-caption-label text-[10px] text-on-secondary-container"><?= (int) $slot['seats_left'] ?> available seats</span>
    </a>

<?php else: ?>
    <a href="<?= e($bookingUrl) ?>" class="w-full p-2.5 rounded-xl bg-tertiary-container hover:brightness-110 active:scale-95 transition-all text-on-surface text-center flex flex-col items-center">
        <span class="font-eyebrow-caps text-[11px] text-on-surface/90">Screen <?= (int) $slot['screen'] ?></span>
        <span class="font-time-slot-headline text-time-slot-headline font-bold text-white tracking-tight"><?= e($slot['time']) ?></span>
        <span class="font-caption-label text-[10px] text-on-surface/80"><?= e($slot['format']) ?></span>
    </a>
<?php endif; ?>
