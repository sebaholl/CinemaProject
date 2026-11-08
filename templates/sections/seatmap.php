<?php
// Booking page body. Needs: $showing (array), $seatRows (array), $view ('2d' | '3d').
// Presentation only — all data is prepared in booking.php.

$availableCount = 0;
foreach ($seatRows as $row) {
    foreach ($row['seats'] as $s) {
        if ($s['state'] === 'available') {
            $availableCount++;
        }
    }
}

$backUrl = url('booking.php?showing_id=' . (int) $showing['id']);                 // 2D view
$view3dUrl = url('booking.php?showing_id=' . (int) $showing['id'] . '&view=3d');  // 3D view

// Legend for the seating plan (matches the .seat CSS colours).
// The type (standard | luxury | accessible) picks the glyph via seat_icon().
$seatLegend = [
    ['seat--standard',   'standard',   'Available'],
    ['seat--selected',   'standard',   'Your seats'],
    ['seat--sold',       'standard',   'Sold'],
    ['seat--luxury',     'luxury',     'Luxury'],
    ['seat--accessible', 'accessible', 'Accessible'],
];
?>

<!-- Mini hero: the showing you are booking, styled like the front-page hero -->
<section class="relative w-full overflow-hidden border-b border-surface-container-highest/30">
    <div class="absolute inset-0 z-0">
        <div class="w-full h-full bg-cover bg-center opacity-25 scale-105 blur-sm" style="background-image: url('<?= e($showing['backdrop']) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-surface-container-lowest/85 to-surface-container-lowest/40"></div>
    </div>

    <div class="relative z-10 w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-lg">
        <a href="<?= e(url('index.php')) ?>#showtimes-section" class="inline-flex items-center gap-space-xs text-on-surface-variant hover:text-on-surface transition-colors font-caption-label text-caption-label">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            All showtimes
        </a>

        <h1 class="mt-space-sm font-headline-lg text-headline-lg md:text-display-xl text-on-surface tracking-tight"><?= e($showing['title']) ?></h1>

        <div class="mt-space-sm flex flex-wrap items-center gap-x-space-lg gap-y-space-xs text-on-surface-variant font-caption-label text-caption-label">
            <span class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-[16px] text-secondary">calendar_today</span><?= e($showing['date_label']) ?></span>
            <span class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-[16px] text-secondary">schedule</span><?= e($showing['time']) ?></span>
            <span class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-[16px] text-secondary">theaters</span>Screen <?= (int) $showing['screen'] ?></span>
            <span class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-[16px] text-secondary">movie</span><?= e($showing['format']) ?></span>
            <span class="flex items-center gap-space-xs"><span class="material-symbols-outlined text-[16px] text-secondary">timelapse</span><?= e(format_duration($showing['duration'])) ?></span>
        </div>
    </div>
</section>

<!-- Main booking area: left = tickets + summary, right = seating plan -->
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">

        <!-- LEFT COLUMN -->
        <div class="lg:col-span-4 flex flex-col gap-space-lg lg:sticky lg:top-24">

            <!-- Member promo (links to the login page) -->
            <div class="flex items-start gap-space-md p-space-md rounded-xl bg-surface-container-low border border-surface-container-highest/30">
                <div class="flex flex-col gap-space-xs">
                    <h2 class="font-body-bold text-body-bold text-on-surface">Sign in to Lumen Club</h2>
                    <p class="font-caption-label text-caption-label text-on-surface-variant">Members save up to 10 kr on every ticket and always get the lowest price.</p>
                </div>
                <a href="<?= e(url('login.php')) ?>" class="shrink-0 inline-flex items-center justify-center px-space-md py-space-xs rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">Log In</a>
            </div>

            <!-- Ticket quantity -->
            <div class="p-space-lg rounded-xl bg-surface-container-low border border-surface-container-highest/30">
                <h2 class="font-headline-lg text-subheading text-on-surface tracking-tight">Select tickets</h2>
                <div class="mt-space-md flex items-center justify-between gap-space-md border-t border-surface-container-highest/30 pt-space-md">
                    <div class="flex flex-col">
                        <span class="font-body-bold text-body-bold text-on-surface">Standard</span>
                        <span class="font-caption-label text-caption-label text-on-surface-variant"><?= (int) Pricing::STANDARD ?> kr incl. <?= (int) Pricing::BOOKING_FEE ?> kr booking fee</span>
                    </div>
                    <div class="flex items-center gap-space-sm">
                        <button type="button" data-step="dec" aria-label="Fewer tickets"
                                class="w-9 h-9 rounded-lg bg-surface-container-high text-on-surface flex items-center justify-center hover:bg-surface-container-highest disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            <span class="material-symbols-outlined text-[20px]">remove</span>
                        </button>
                        <span id="ticket-count" class="w-6 text-center font-body-bold text-body-bold text-on-surface" aria-live="polite">2</span>
                        <button type="button" data-step="inc" aria-label="More tickets"
                                class="w-9 h-9 rounded-lg bg-primary-container text-on-primary flex items-center justify-center hover:bg-primary disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            <span class="material-symbols-outlined text-[20px]">add</span>
                        </button>
                    </div>
                </div>
                <p class="mt-space-sm font-caption-label text-caption-label text-on-surface-variant">Up to 5 seats per booking. Luxury seats cost <?= (int) Pricing::LUXURY ?> kr.</p>
            </div>

            <!-- Order summary -->
            <form id="booking-form" method="post" action="<?= e(url('checkout.php')) ?>" class="p-space-lg rounded-xl bg-surface-container-low border border-surface-container-highest/30 flex flex-col gap-space-md">
                <?= csrf_field() ?>
                <input type="hidden" name="step" value="select">
                <input type="hidden" name="showing_id" value="<?= (int) $showing['id'] ?>">
                <input type="hidden" name="seats" id="seats-field" value="">

                <h2 class="font-headline-lg text-subheading text-on-surface tracking-tight">Your seats</h2>

                <ul id="selected-list" class="flex flex-col gap-space-xs min-h-[1.5rem]">
                    <li class="font-caption-label text-caption-label text-on-surface-variant italic">No seats selected yet.</li>
                </ul>

                <div class="flex items-center justify-between border-t border-surface-container-highest/30 pt-space-md">
                    <span class="font-body-bold text-body-bold text-on-surface">Total</span>
                    <span id="selected-total" class="font-headline-lg text-subheading text-on-surface">0 kr</span>
                </div>

                <p id="selected-hint" class="font-caption-label text-caption-label text-secondary">Select 2 seats from the plan.</p>

                <button type="submit" id="continue-btn" disabled
                        class="w-full inline-flex items-center justify-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                    Continue to checkout
                </button>
            </form>
        </div>

        <!-- RIGHT COLUMN: seating plan -->
        <div class="lg:col-span-8">
            <div class="rounded-xl bg-surface-container-low border border-surface-container-highest/30 p-space-lg md:p-space-xl">

                <div class="flex items-center justify-between gap-space-md">
                    <h2 class="font-headline-lg text-subheading text-on-surface tracking-tight">Choose your seats</h2>
                    <span class="font-caption-label text-caption-label text-on-surface-variant"><?= (int) $availableCount ?> seats available</span>
                </div>

                <?php if ($view === '3d'): ?>
                    <!-- 3D view: mount point for the Three.js seat picker prototype -->
                    <div class="mt-space-lg">
                        <div id="seat-3d-root"
                             data-showing-id="<?= (int) $showing['id'] ?>"
                             data-seats-url="<?= e(url('api/seats.php?showing_id=' . (int) $showing['id'])) ?>"
                             class="relative w-full aspect-[16/9] rounded-xl bg-surface-container-lowest border border-surface-container-highest/30 overflow-hidden flex flex-col items-center justify-center text-center gap-space-sm">
                            <span class="material-symbols-outlined text-[40px] text-on-surface-variant">view_in_ar</span>
                            <p class="font-body-bold text-body-bold text-on-surface">3D seat view</p>
                            <p class="max-w-md px-space-lg font-caption-label text-caption-label text-on-surface-variant">
                                The Three.js seat picker mounts here and reads the same seat data as the 2D plan.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Screen -->
                    <div class="mt-space-lg mb-space-xl flex flex-col items-center gap-space-xs">
                        <div class="h-[6px] w-[85%] rounded-[50%] bg-surface-container-highest shadow-[0_-10px_34px_-6px_rgba(226,226,232,0.35)]"></div>
                        <span class="font-eyebrow-caps text-eyebrow-caps text-on-surface-variant uppercase tracking-[0.3em]">Screen</span>
                    </div>

                    <!-- Rows -->
                    <div class="w-full overflow-x-auto">
                        <div class="mx-auto flex flex-col items-center gap-space-xs min-w-[640px] w-fit">
                            <?php foreach ($seatRows as $row): ?>
                                <div class="flex items-center gap-space-sm <?= $row['row'] === 7 ? 'mt-space-lg' : '' ?>">
                                    <span class="w-5 text-right font-caption-label text-[11px] text-on-surface-variant/70"><?= (int) $row['row'] ?></span>
                                    <div class="flex items-center gap-1">
                                        <?php foreach ($row['seats'] as $seat): ?>
                                            <?php include __DIR__ . '/../partials/seat_button.php'; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <span class="w-5 text-left font-caption-label text-[11px] text-on-surface-variant/70"><?= (int) $row['row'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Legend -->
                <div class="mt-space-xl pt-space-md border-t border-surface-container-highest/30 flex flex-wrap items-center gap-x-space-lg gap-y-space-sm">
                    <?php foreach ($seatLegend as [$cls, $seatType, $label]): ?>
                        <span class="flex items-center gap-space-xs font-caption-label text-caption-label text-on-surface-variant">
                            <span class="seat seat--legend <?= e($cls) ?>" style="pointer-events:none;"><?= seat_icon($seatType) ?></span>
                            <?= e($label) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating toggle between the 2D plan and the 3D viewer -->
<?php if ($view === '3d'): ?>
    <a href="<?= e($backUrl) ?>"
       class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-space-xs pl-space-md pr-space-lg py-space-md rounded-xl bg-surface-container-high text-on-surface font-body-bold text-caption-label uppercase tracking-wider shadow-[0_6px_24px_rgba(0,0,0,0.5)] hover:bg-surface-container-highest transition-colors">
        <span class="material-symbols-outlined text-[20px]">grid_view</span>
        Back to seat map
    </a>
<?php else: ?>
    <a href="<?= e($view3dUrl) ?>"
       class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-space-xs pl-space-md pr-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider shadow-[0_6px_24px_rgba(255,81,103,0.4)] hover:bg-primary transition-colors">
        <span class="material-symbols-outlined text-[20px]">view_in_ar</span>
        View in 3D
    </a>
<?php endif; ?>

<?php if ($view !== '3d'): ?>
    <script src="<?= e(asset('js/booking.js')) ?>"></script>
<?php endif; ?>
