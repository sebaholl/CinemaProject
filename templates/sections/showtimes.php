<?php
// Weekly showtime matrix: one row per movie, one column per day.
// Needs: $days (7 days), $schedule (movies with a list of slots per day)
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl" id="showtimes-section">
    <div class="flex flex-col gap-space-lg">

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md pb-space-xs">
            <div class="space-y-space-xs">
                <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">This Week's Programme &amp; Showtimes</h2>
                <p class="font-body-regular text-body-regular text-on-surface-variant">Select a date to reserve your favorite seats in our 6 custom-designed auditoriums.</p>
            </div>

            <!-- Date pills (behaviour in assets/js/main.js) -->
            <div class="inline-flex p-1 bg-surface-container rounded-full overflow-x-auto max-w-full">
                <?php foreach ($days as $i => $day): ?>
                    <button type="button" data-day-pill="<?= (int) $i ?>"
                            class="px-space-md py-space-xs rounded-full font-body-regular text-caption-label whitespace-nowrap transition-colors hover:text-on-surface <?= $i === 0 ? 'bg-primary-container text-on-primary shadow-[0_0_16px_rgba(255,81,103,0.35)]' : 'text-on-surface-variant' ?>">
                        <?= e($day['pill']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="w-full bg-surface-container-low rounded-xl p-space-md md:p-space-lg flex flex-col gap-space-xl overflow-x-auto">
            <?php foreach ($schedule as $row => $movie): ?>
                <div class="min-w-[1040px] flex gap-space-lg items-start <?= $row > 0 ? 'pt-space-md border-t border-surface-container-highest/30' : '' ?>">

                    <!-- Poster + title -->
                    <div class="w-48 flex-shrink-0 flex flex-col gap-space-sm">
                        <div class="aspect-[2/3] w-full rounded-lg overflow-hidden shadow-md bg-surface-container">
                            <img class="w-full h-full object-cover" alt="Poster for <?= e($movie['title']) ?>" src="<?= e($movie['poster']) ?>">
                        </div>
                        <div class="flex flex-col gap-1">
                            <h3 class="font-headline-lg text-subheading text-on-surface font-bold tracking-tight"><?= e($movie['title']) ?></h3>
                            <p class="font-caption-label text-caption-label text-on-surface-variant">Duration: <?= e(format_duration($movie['duration'])) ?></p>
                        </div>
                    </div>

                    <!-- 7 day columns -->
                    <div class="flex-1 grid grid-cols-7 gap-space-sm">
                        <?php foreach ($days as $i => $day): ?>
                            <div class="flex flex-col gap-space-sm transition-opacity" data-day-col="<?= (int) $i ?>">
                                <!-- Date header repeated above every movie's showings, not just the first row -->
                                <div class="text-center pb-space-xs border-b border-surface-container-highest/20 font-body-bold text-caption-label text-on-surface"><?= e($day['label']) ?></div>
                                <div class="flex flex-col gap-space-sm">
                                    <?php foreach ($movie['days'][$i] as $slot): ?>
                                        <?php include __DIR__ . '/../partials/showtime_slot.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php include __DIR__ . '/../partials/availability_legend.php'; ?>
        </div>
    </div>
</section>
