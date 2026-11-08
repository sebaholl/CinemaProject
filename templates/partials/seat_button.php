<?php
// One seat in the seating plan. Needs: $seat (array from vm_seat_rows()).
// Sold seats render as a non-clickable icon. Free seats are buttons the JS can toggle.
$type  = $seat['type'];                       // standard | luxury | accessible
$state = $seat['state'];                       // available | sold
$label = 'Row ' . $seat['row'] . ', seat ' . $seat['num'];

// Accessible seats keep the wheelchair glyph; everything else uses the chair SVG.
// seat_icon() is defined in templates/partials/seat_icon.php.
?>
<?php if ($state === 'sold'): ?>
    <span class="seat seat--sold" title="<?= e($label) ?> — sold" aria-hidden="true">
        <?= seat_icon($type) ?>
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
        <?= seat_icon($type) ?>
    </button>
<?php endif; ?>
