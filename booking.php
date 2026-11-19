<?php
// Booking page: seating plan for one screening.
// Reached from the showtime buttons, e.g. booking.php?showing_id=1.
// The controller reads data; templates render the page.
require_once __DIR__ . '/includes/bootstrap.php';

// Read the showing ID from the URL.
$showingId = isset($_GET['showing_id'])
    ? (int) $_GET['showing_id']
    : 0;

// 2D plan by default; ?view=3d opens the 3D viewer.
$view = (isset($_GET['view']) && $_GET['view'] === '3d')
    ? '3d'
    : '2d';

$pdo         = Database::getInstance()->getConnection();
$showingRepo = new ShowingRepository($pdo);
$screening   = $showingRepo->findById($showingId);

// Unknown showing: show a friendly 404.
if ($screening === null) {
    http_response_code(404);
    $pageTitle = 'Showing not found';
    $currentPage = '';

    require __DIR__ . '/templates/layout/header.php';
    ?>
    <section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl text-center">
        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">
            Showing not found
        </h1>
        <p class="mt-space-sm font-body-regular text-body-regular text-on-surface-variant">
            That screening does not exist or is no longer available.
        </p>
        <a href="<?= e(url('index.php')) ?>#showtimes-section"
           class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to showtimes
        </a>
    </section>
    <?php
    require __DIR__ . '/templates/layout/footer.php';
    exit;
}

// Movie details supply the runtime and backdrop image.
$movie = (new MovieRepository($pdo))->findById(
    $screening->getMovieId()
);

$showing = [
    'id'         => $screening->getId(),
    'title'      => $screening->getMovieTitle(),
    'duration'   => $movie ? $movie->getDurationMinutes() : 0,
    'screen'     => $screening->getHallId(),
    'time'       => substr($screening->getTime(), 0, 5),
    'format'     => $screening->getFormat(),
    'date_label' => format_showing_date($screening->getDate()),
    'backdrop'   => ($movie && $movie->getCoverImage())
        ? $movie->getCoverImage()
        : 'https://images.alphacoders.com/141/thumb-1920-1412625.jpg',
    'price'      => $screening->getPriceKr(),
];

// The 3D viewer has its own complete HTML document.
if ($view === '3d') {
    require __DIR__ . '/templates/sections/seatmap_3d.php';
    exit;
}

// The 2D picker reads seats for this showing and prices them using its
// standard ticket price.
$seatRows = vm_seat_rows(
    (new SeatRepository($pdo))->findForScreening($showingId),
    $screening->getPriceKr()
);

$pageTitle = 'Book · ' . $showing['title'];
$currentPage = '';

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/sections/seatmap.php';
require __DIR__ . '/templates/layout/footer.php';