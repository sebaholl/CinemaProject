<?php
// One seat in the seating plan. Needs: $seat (array from vm_seat_rows()).
// Sold seats render as a non-clickable icon. Free seats are buttons the JS can toggle.
$type  = $seat['type'];                       // standard | luxury | accessible
$state = $seat['state'];                       // available | sold
$icon  = $type === 'accessible' ? 'accessible' : 'event_seat';
$label = 'Row ' . $seat['row'] . ', seat ' . $seat['num'];
?>
<?php if ($state === 'sold'): ?>
    <span class="seat seat--sold" title="<?= e($label) ?> — sold" aria-hidden="true">
        <span class="material-symbols-outlined">event_seat</span>
    </span>
<?php else: ?>
    <button type="button"
            class="seat seat--<?= e($type) ?>"
            data-seat
            data-seat-id="<?= e($seat['id']) ?>"
            data-row="<?= (int) $seat['row'] ?>"
            data-num="<?= (int) $seat['num'] ?>"
            data-type="<?= e($type) ?>"
            data-price="<?= (int) $seat['price'] ?>"
            aria-pressed="false"
            aria-label="<?= e($label . ', ' . ucfirst($type) . ', ' . $seat['price'] . ' kr') ?>">
        <span class="material-symbols-outlined"><?= e($icon) ?></span>
    </button>
<?php endif; ?>
