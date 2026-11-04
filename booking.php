<?php
// Booking page: seating plan for one showing.
// Reached from the showtime buttons, e.g. booking.php?showing_id=100
// Only prepares data here; the HTML lives in the templates.
require_once __DIR__ . '/includes/bootstrap.php';

// Read and validate the showing id from the URL.
$showingId = isset($_GET['showing_id']) ? (int) $_GET['showing_id'] : 0;

// 2D plan by default; ?view=3d switches to the 3D viewer.
$view = (isset($_GET['view']) && $_GET['view'] === '3d') ? '3d' : '2d';

// TEMPORARY sample data. Later replaced by the Showing / Seat classes reading the DB.
$showing = SampleData::showingById($showingId);

// Unknown showing: show a friendly 404 instead of a broken page.
if ($showing === null) {
    http_response_code(404);
    $pageTitle   = 'Showing not found';
    $currentPage = '';
    require __DIR__ . '/templates/layout/header.php';
    ?>
    <section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl text-center">
        <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Showing not found</h1>
        <p class="mt-space-sm font-body-regular text-body-regular text-on-surface-variant">That screening does not exist or is no longer available.</p>
        <a href="<?= e(url('index.php')) ?>#showtimes-section" class="mt-space-lg inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to showtimes
        </a>
    </section>
    <?php
    require __DIR__ . '/templates/layout/footer.php';
    exit;
}

$seatRows = SampleData::seatMap($showingId);

$pageTitle   = 'Book · ' . $showing['title'];
$currentPage = '';   // not a main-nav page, so nothing is highlighted

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/sections/seatmap.php';
require __DIR__ . '/templates/layout/footer.php';
