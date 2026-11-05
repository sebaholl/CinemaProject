<?php
declare(strict_types=1);

// Booking confirmation, shown after a successful mock checkout (via a redirect, so
// refreshing it never re-books). Reads the finished booking from the DB.
//
// NOTE: there is no login yet, so this does not check who owns the booking. Once
// real accounts exist, verify the booking belongs to the logged-in customer before
// showing it, otherwise anyone could view a booking by changing the id in the URL.
require_once __DIR__ . '/includes/bootstrap.php';

$bookingId = isset($_GET['booking']) ? (int) $_GET['booking'] : 0;

$pdo       = Database::getInstance()->getConnection();
$bookRepo  = new BookingRepository($pdo);
$overview  = $bookRepo->findOverview($bookingId);

$pageTitle   = 'Booking confirmed';
$currentPage = '';
require __DIR__ . '/templates/layout/header.php';

if ($overview === null): ?>
    <section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl text-center">
        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Booking not found</h1>
        <p class="mt-space-md font-body-regular text-body-regular text-on-surface-variant">We couldn't find that booking.</p>
        <a href="<?= e(url('index.php')) ?>" class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">Back to home</a>
    </section>
<?php else:
    $seatLabels = $bookRepo->findSeatLabels($bookingId);
?>
    <section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
        <div class="p-space-lg md:p-space-xl rounded-xl bg-surface-container-low border border-surface-container-highest/30">
            <div class="flex items-center gap-space-md">
                <span class="material-symbols-outlined text-[40px] text-tertiary">check_circle</span>
                <div>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Booking confirmed</h1>
                    <p class="font-caption-label text-caption-label text-on-surface-variant">Booking #<?= (int) $overview['booking_id'] ?><?= $overview['invoice_id'] ? ' · Invoice #' . (int) $overview['invoice_id'] : '' ?></p>
                </div>
            </div>

            <dl class="mt-space-lg grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                <div>
                    <dt class="font-caption-label text-caption-label text-on-surface-variant">Film</dt>
                    <dd class="font-body-bold text-body-bold text-on-surface"><?= e($overview['movie_title']) ?></dd>
                </div>
                <div>
                    <dt class="font-caption-label text-caption-label text-on-surface-variant">When</dt>
                    <dd class="font-body-bold text-body-bold text-on-surface"><?= e(format_showing_date($overview['screening_date'])) ?> · <?= e(substr((string) $overview['screening_time'], 0, 5)) ?></dd>
                </div>
                <div>
                    <dt class="font-caption-label text-caption-label text-on-surface-variant">Auditorium</dt>
                    <dd class="font-body-bold text-body-bold text-on-surface"><?= e($overview['hall_name']) ?></dd>
                </div>
                <div>
                    <dt class="font-caption-label text-caption-label text-on-surface-variant">Seats</dt>
                    <dd class="font-body-bold text-body-bold text-on-surface"><?= e(implode(', ', $seatLabels)) ?></dd>
                </div>
            </dl>

            <div class="mt-space-lg flex items-center justify-between border-t border-surface-container-highest/30 pt-space-md">
                <span class="font-body-bold text-body-bold text-on-surface">Total paid</span>
                <span class="font-headline-lg text-subheading text-on-surface"><?= (int) round((float) $overview['price']) ?> kr</span>
            </div>

            <a href="<?= e(url('index.php')) ?>" class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">home</span>
                Back to home
            </a>
        </div>
    </section>
<?php endif;

require __DIR__ . '/templates/layout/footer.php';
