<?php
// Hero: featured movie, today's showtime chips, and the poster row.
// Needs: $featured, $todayShowings, $nowShowing
// Background comes from the featured movie's backdrop_image; falls back to a generic image.
$heroBg = !empty($featured['backdrop'])
    ? $featured['backdrop']
    : 'https://images.alphacoders.com/141/thumb-1920-1412625.jpg';
?>
<section class="relative w-full -mt-20 overflow-hidden bg-surface-container-lowest">
    <!-- Background image with dark gradients on top -->
    <div class="absolute inset-0 z-0">
        <div class="w-full h-full bg-contain bg-no-repeat bg-center md:bg-right opacity-30 filter blur-sm" style="background-image: url('<?= e($heroBg) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-surface-container-lowest/80 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest via-surface-container-lowest/70 to-transparent"></div>
    </div>

    <div class="relative z-10 w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl flex flex-col justify-between">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
            <div class="lg:col-span-7 flex flex-col gap-space-md">
                <h1 class="font-display-xl text-display-xl text-on-surface tracking-tight uppercase"><?= e($featured['title']) ?></h1>

                <div class="flex flex-wrap items-center gap-y-space-xs gap-x-space-md text-on-surface-variant font-caption-label text-caption-label">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px] text-secondary">schedule</span>
                        <?= e(format_duration($featured['duration'])) ?>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-space-md pt-space-xs">
                    <a class="inline-flex items-center justify-center gap-space-xs px-7 py-3 rounded-full bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider shadow-lg hover:shadow-[0_4px_24px_rgba(255,81,103,0.45)] hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 font-bold" href="#showtimes-section">
                        <span class="material-symbols-outlined text-[18px] font-bold">confirmation_number</span>
                        Book Tickets
                    </a>
                    <button class="inline-flex items-center justify-center gap-space-xs px-6 py-3 rounded-full bg-surface-container text-on-surface font-body-bold text-caption-label tracking-wide hover:bg-surface-container-high transition-colors" type="button">
                        <span class="material-symbols-outlined text-[18px]">play_circle</span>
                        Watch Trailer
                    </button>
                </div>

                <!-- Today's showtime chips -->
                <div class="pt-space-sm flex flex-col gap-space-xs">
                    <span class="font-eyebrow-caps text-eyebrow-caps text-on-surface-variant uppercase tracking-widest">Next available showings today:</span>
                    <div class="flex flex-wrap items-center gap-space-sm">
                        <?php foreach ($todayShowings as $slot):
                            $status = seat_status($slot['seats_left'], $slot['capacity']);
                            $isFew = in_array($status, ['low', 'critical'], true);
                        ?>
                            <a class="flex items-center gap-space-sm px-space-md py-space-xs rounded-full bg-surface-container hover:bg-surface-container-high transition-colors text-on-surface font-bold"
                               href="<?= e(url('booking.php?showing_id=' . (int) $slot['id'])) ?>">
                                <span class="w-2.5 h-2.5 rounded-full <?= $isFew ? 'bg-secondary shadow-[0_0_8px_rgba(255,185,76,0.6)]' : 'bg-tertiary shadow-[0_0_8px_rgba(81,223,154,0.6)]' ?>"></span>
                                <span class="font-time-slot-headline text-time-slot-headline"><?= e($slot['time']) ?></span>
                                <span class="font-caption-label text-caption-label <?= $isFew ? 'text-secondary font-bold' : 'text-on-surface-variant' ?>">
                                    Screen <?= (int) $slot['screen'] ?><?= $isFew ? ' • Few seats' : '' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Poster row -->
        <div class="w-full pt-space-xl">
            <div class="flex items-center justify-between pb-space-sm">
                <span class="font-eyebrow-caps text-eyebrow-caps text-on-surface-variant uppercase tracking-widest">Now Showing in <?= e(SITE_NAME) ?> Auditoriums</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-space-md">
                <?php foreach ($nowShowing as $i => $movie): ?>
                    <?php $isActive = ($i === 0); include __DIR__ . '/../partials/movie_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
