<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$auth = new Auth($pdo);
$auth->requireLogin();

$user = $auth->currentUser();

if ($user === null || $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Customer account required.');
}

$bookingId = filter_input(INPUT_GET, 'booking', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$bookings = new BookingRepository($pdo);
$invoice = $bookingId
    ? $bookings->findOwnedOverview((int) $bookingId, (int) $user['id'])
    : null;

if ($invoice === null || $invoice['invoice_id'] === null) {
    http_response_code(404);
    exit('Invoice not found.');
}

$seats = $invoice['status'] === 'cancelled'
    ? 'Released'
    : implode(', ', $bookings->findSeatLabels((int) $bookingId));

header('Cache-Control: private, no-store');

$pageTitle = 'Invoice #' . $invoice['invoice_id'];
$currentPage = '';

require __DIR__ . '/templates/layout/header.php';
?>

<section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <div class="p-space-lg md:p-space-xl rounded-xl bg-surface-container-low">
        <h1 class="font-headline-lg text-headline-lg text-on-surface">
            Invoice #<?= (int) $invoice['invoice_id'] ?>
        </h1>

        <p class="mt-space-sm text-on-surface-variant">
            Mock checkout · No real payment was taken
        </p>

        <dl class="mt-space-lg flex flex-col gap-space-md">
            <div>
                <dt class="text-on-surface-variant">Customer</dt>
                <dd><?= e($invoice['customer_name']) ?></dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Invoice date</dt>
                <dd><?= e($invoice['invoice_date']) ?></dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Movie</dt>
                <dd><?= e($invoice['movie_title']) ?></dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Screening</dt>
                <dd>
                    <?= e($invoice['screening_date']) ?>
                    at <?= e(substr((string) $invoice['screening_time'], 0, 5)) ?>
                    · <?= e($invoice['hall_name']) ?>
                </dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Seats</dt>
                <dd><?= e($seats) ?></dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Booking status</dt>
                <dd><?= e($invoice['status']) ?></dd>
            </div>
            <div>
                <dt class="text-on-surface-variant">Total</dt>
                <dd>
                    <?= e(number_format((float) $invoice['invoice_total'], 2, '.', '')) ?> kr
                </dd>
            </div>
        </dl>

        <a href="<?= e(url('profile.php')) ?>"
           class="mt-space-lg inline-block text-primary hover:underline">
            Back to my bookings
        </a>
    </div>
</section>

<?php require __DIR__ . '/templates/layout/footer.php'; ?>