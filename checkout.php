<?php
declare(strict_types=1);

// Mock checkout, shared by the 2D and 3D seat pickers. Two steps, both POST:
//   step=select : seats arrive from the booking page -> validate -> show card form
//   step=pay    : card form submitted -> take payment -> write the booking -> redirect
// Separating the steps means the "seats chosen" POST can never be mistaken for a
// "payment" POST. The validated selection lives in the session between the two.
//
// Real card data is never stored. The seat rules are enforced HERE (server-side),
// not just in the browser.
require_once __DIR__ . '/includes/bootstrap.php';

/** Parse a "12,8,30" seats string into a clean list of positive ints. */
function parse_seat_ids(string $raw): array
{
    $ids = array_map('intval', array_filter(explode(',', $raw), static fn($v) => $v !== ''));
    $ids = array_values(array_unique(array_filter($ids, static fn($id) => $id > 0)));
    return $ids;
}

$pdo       = Database::getInstance()->getConnection();
$showRepo  = new ShowingRepository($pdo);
$seatRepo  = new SeatRepository($pdo);
$bookRepo  = new BookingRepository($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$step   = $_POST['step'] ?? '';

// Direct GET or anything unexpected: nothing to check out, go home.
if ($method !== 'POST' || !in_array($step, ['select', 'pay'], true)) {
    header('Location: ' . url('index.php'));
    exit;
}

// Both steps are state-changing POSTs, so both need a valid CSRF token.
if (!csrf_check()) {
    http_response_code(400);
    $pageError = 'Your session token was invalid. Please go back and try again.';
    render_checkout_error($pageError);
    exit;
}

$auth = new Auth($pdo);
$user = $auth->currentUser();

if ($user === null) {
    $returnShowingId = 0;

    if ($step === 'select' &&
        is_string($_POST['showing_id'] ?? null) &&
        ctype_digit($_POST['showing_id'])) {
        $returnShowingId = (int) $_POST['showing_id'];
    } elseif (isset($_SESSION['checkout']['showing_id'])) {
        $returnShowingId = (int) $_SESSION['checkout']['showing_id'];
    }

    $returnTo = $returnShowingId > 0
        ? url('booking.php?showing_id=' . $returnShowingId)
        : url('index.php');

    header(
        'Location: ' . url(
            'login.php?return=' . rawurlencode($returnTo)
        ),
        true,
        303
    );
    exit;
}

if ($user['role'] !== 'customer') {
    http_response_code(403);
    exit('Customer account required to book seats.');
}

// ---------------------------------------------------------------------------
// STEP 1 — seats chosen on the booking page. Validate and show the card form.
// ---------------------------------------------------------------------------
if ($step === 'select') {
    $showingId = (int) ($_POST['showing_id'] ?? 0);
    $seatIds   = parse_seat_ids((string) ($_POST['seats'] ?? ''));

    $screening = $showRepo->findById($showingId);
    if ($screening === null) {
        render_checkout_error('That screening could not be found.');
        exit;
    }

    // Max-5 rule counts the customer's existing seats for this showing + the new ones.
    // Guests (no login yet) count 0, so they stay capped per booking; see current_customer_id().
    $customerId = current_customer_id();
    $existing   = $customerId !== null
        ? $bookRepo->countConfirmedSeatsForCustomer($showingId, $customerId)
        : 0;

    $allSeats = $seatRepo->findForScreening($showingId);
    $errors   = SeatRules::validate($allSeats, $seatIds, $existing);
    if ($errors !== []) {
        render_checkout_error(implode(' ', $errors), $showingId);
        exit;
    }

    // Keep the validated selection server-side; the pay step reads it from here,
    // never from the browser again.
    $_SESSION['checkout'] = [
        'showing_id' => $showingId,
        'seat_ids'   => $seatIds,
        'created'    => time(),
    ];

    render_card_form($screening, $allSeats, $seatIds);
    exit;
}

// ---------------------------------------------------------------------------
// STEP 2 — card form submitted. Take the mock payment and write the booking.
// ---------------------------------------------------------------------------
$pending = $_SESSION['checkout'] ?? null;
if (!is_array($pending) || empty($pending['seat_ids']) || (time() - (int) $pending['created'] > 1800)) {
    render_checkout_error('Your checkout session expired. Please choose your seats again.');
    exit;
}

$showingId = (int) $pending['showing_id'];
$seatIds   = array_map('intval', $pending['seat_ids']);

$screening = $showRepo->findById($showingId);
if ($screening === null) {
    unset($_SESSION['checkout']);
    render_checkout_error('That screening could not be found.');
    exit;
}

// Re-read availability and re-check the rules: a seat may have gone in the meantime.
$customerId = current_customer_id();
$existing   = $customerId !== null
    ? $bookRepo->countConfirmedSeatsForCustomer($showingId, $customerId)
    : 0;

$allSeats = $seatRepo->findForScreening($showingId);
$errors   = SeatRules::validate($allSeats, $seatIds, $existing);
if ($errors !== []) {
    render_card_form($screening, $allSeats, $seatIds, implode(' ', $errors));
    exit;
}

// Mock payment outcome. We never store or log the number — we only look at its last
// digits to decide the demo result: a card ending in 0000 is declined, anything else
// is approved.
$cardRaw = preg_replace('/\D+/', '', (string) ($_POST['card_number'] ?? ''));
if (strlen($cardRaw) < 12) {
    render_card_form($screening, $allSeats, $seatIds, 'Please enter a valid card number.');
    exit;
}
if (str_ends_with($cardRaw, '0000')) {
    render_card_form($screening, $allSeats, $seatIds, 'Payment was declined. Try a different card.');
    exit;
}

// Payment "succeeded" — create the booking (this is the real seat lock).
// Attribute it to the logged-in customer, or the demo guest until login exists.
try {
    $booking = $bookRepo->create($showingId, (int) $user['id'], $allSeats, $seatIds);
} catch (BookingException $e) {
    render_card_form($screening, $allSeats, $seatIds, $e->getMessage());
    exit;
}

// Done. Clear the pending selection and redirect (Post/Redirect/Get) so a refresh
// of the confirmation page cannot create a second booking.
unset($_SESSION['checkout']);
header('Location: ' . url('confirmation.php?booking=' . $booking->getId()));
exit;


// ---------------------------------------------------------------------------
// Presentation helpers. Kept as functions so the control flow above stays readable;
// they render with the normal site header/footer.
// ---------------------------------------------------------------------------

/** @param Seat[] $allSeats */
function render_card_form($screening, array $allSeats, array $seatIds, ?string $cardError = null): void
{
    // Build the order summary from DB prices — the browser total is never trusted.
    $byId  = [];
    foreach ($allSeats as $s) { $byId[$s->getId()] = $s; }

    $orderSeats = [];
    $total = 0;
    foreach ($seatIds as $id) {
        if (!isset($byId[$id])) { continue; }
        $seat  = $byId[$id];
        $price = Pricing::priceFor($seat->getSeatType());
        $total += $price;
        $orderSeats[] = ['label' => $seat->getRowLabel() . $seat->getSeatNumber(), 'price' => $price];
    }

    $showing = [
        'id'         => $screening->getId(),
        'title'      => $screening->getMovieTitle(),
        'screen'     => $screening->getHallId(),
        'time'       => substr($screening->getTime(), 0, 5),
        'date_label' => format_showing_date($screening->getDate()),
    ];

    $pageTitle   = 'Checkout · ' . $showing['title'];
    $currentPage = '';
    require __DIR__ . '/templates/layout/header.php';
    require __DIR__ . '/templates/sections/checkout.php';
    require __DIR__ . '/templates/layout/footer.php';
}

function render_checkout_error(string $message, int $showingId = 0): void
{
    $backUrl     = $showingId > 0 ? url('booking.php?showing_id=' . $showingId) : url('index.php');
    $pageTitle   = 'Checkout';
    $currentPage = '';
    require __DIR__ . '/templates/layout/header.php';
    ?>
    <section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl text-center">
        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">We hit a snag</h1>
        <p class="mt-space-md font-body-regular text-body-regular text-on-surface-variant"><?= e($message) ?></p>
        <a href="<?= e($backUrl) ?>" class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to seat selection
        </a>
    </section>
    <?php
    require __DIR__ . '/templates/layout/footer.php';
}
