<?php
// Front page: hero, featured showings, weekly programme, and news.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Home';
$currentPage = 'home';

$pdo         = Database::getInstance()->getConnection();
$company     = (new CompanyRepository($pdo))->find();
$movieRepo   = new MovieRepository($pdo);
$showingRepo = new ShowingRepository($pdo);
$newsRepo    = new NewsRepository($pdo);

// Movie lookup for the hero and weekly programme.
$movieById = [];
foreach ($movieRepo->findAll() as $movie) {
    $movieById[$movie->getId()] = $movie;
}

// Prefer an admin-selected showing for today's hero.
$featuredToday = $showingRepo->findFeaturedForDate(gmdate('Y-m-d'));
$upcoming = $showingRepo->findUpcoming();
$heroShowing = $featuredToday[0] ?? $upcoming[0] ?? null;

$featuredMovie = $heroShowing !== null
    ? ($movieById[$heroShowing->getMovieId()] ?? null)
    : null;

if ($featuredMovie === null) {
    $featuredMovie = $movieById ? reset($movieById) : null;
}

$featured = $featuredMovie
    ? [
        'title' => $featuredMovie->getTitle(),
        'duration' => $featuredMovie->getDurationMinutes(),
        'description' => $featuredMovie->getDescription(),
    ]
    : [
        'title' => SITE_NAME,
        'duration' => 0,
        'description' => '',
    ];

// Today's showtime chips: only show screenings that have not started.
$nowTime = gmdate('H:i:s');
$todayShowings = [];

foreach ($showingRepo->findToday() as $showing) {
    if ($showing->getTime() > $nowTime) {
        $todayShowings[] = vm_showing_slot($showing);

        if (count($todayShowings) >= 6) {
            break;
        }
    }
}

// Poster row: movies with upcoming screenings, maximum six.
$nowShowing = array_map(
    'vm_movie_card',
    array_slice($movieRepo->findAllWithUpcomingShowings(), 0, 6)
);

// Seven-day programme and featured-day selector.
$days = week_days(7);
$schedule = vm_week_schedule(
    $showingRepo->findWeek(7),
    $movieById,
    $days
);

$featuredDate = $_GET['featured_date'] ?? gmdate('Y-m-d');
$allowedDates = array_column($days, 'date');

if (!is_string($featuredDate)
    || !in_array($featuredDate, $allowedDates, true)) {
    $featuredDate = gmdate('Y-m-d');
}

$featuredShowings = $showingRepo->findFeaturedForDate($featuredDate);

// Latest news.
$events = [];
foreach ($newsRepo->findLatest(3) as $index => $news) {
    $events[] = vm_news_card($news, $index === 0);
}

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/sections/hero.php';
?>

<section class="max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl"
         id="featured-showings">
    <h2 class="text-2xl font-bold mb-4">Featured showings</h2>

    <nav aria-label="Choose featured showing day"
         class="flex flex-wrap gap-3 mb-5">
        <?php foreach ($days as $day): ?>
            <a href="<?= e(url(
                'index.php?featured_date=' . rawurlencode($day['date'])
            )) ?>#featured-showings"
               class="rounded-xl px-3 py-2 <?= $day['date'] === $featuredDate
                   ? 'bg-primary-container text-on-primary'
                   : 'bg-surface-container-high text-on-surface' ?>">
                <?= e($day['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($featuredShowings === []): ?>
        <p class="text-on-surface-variant">
            No featured showings for this day.
        </p>
    <?php else: ?>
        <div class="grid gap-4 md:grid-cols-3">
            <?php foreach ($featuredShowings as $showing): ?>
                <a href="<?= e(url(
                    'booking.php?showing_id=' . $showing->getId()
                )) ?>"
                   class="rounded-xl bg-surface-container-low p-5 hover:bg-surface-container-high">
                    <strong class="block text-lg">
                        <?= e($showing->getMovieTitle()) ?>
                    </strong>
                    <span class="block mt-2 text-on-surface-variant">
                        <?= e($showing->getDate()) ?>
                        at <?= e(substr($showing->getTime(), 0, 5)) ?>
                        · <?= e($showing->getHallName()) ?>
                    </span>
                    <span class="block mt-2">
                        From <?= (int) $showing->getPriceKr() ?> kr
                        · <?= (int) $showing->getSeatsLeft() ?> seats left
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($company !== null
    && trim((string) $company['description']) !== ''): ?>
    <section class="max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl">
        <h2 class="text-2xl font-bold mb-4">About our cinema</h2>
        <p class="whitespace-pre-line text-on-surface-variant max-w-3xl"><?= e($company['description']) ?></p>
    </section>
<?php endif; ?>

<?php
require __DIR__ . '/templates/sections/showtimes.php';
require __DIR__ . '/templates/sections/events.php';
require __DIR__ . '/templates/layout/footer.php';
