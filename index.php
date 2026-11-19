<?php
// Front page: hero, weekly showtimes, events/news.
// This file is the "controller": it reads data through the repositories and
// prepares the arrays the templates need, then includes the templates.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Home';
$currentPage = 'home';

$pdo         = Database::getInstance()->getConnection();
$company     = (new CompanyRepository($pdo))->find();
$movieRepo   = new MovieRepository($pdo);
$showingRepo = new ShowingRepository($pdo);
$newsRepo    = new NewsRepository($pdo);

// Movie lookup (id => Movie) so the weekly matrix can get posters/durations cheaply.
$movieById = [];
foreach ($movieRepo->findAll() as $m) {
    $movieById[$m->getId()] = $m;
}

// Featured = the movie of the soonest upcoming screening (fallback: first movie).
$upcoming      = $showingRepo->findUpcoming();
$featuredMovie = $upcoming ? ($movieById[$upcoming[0]->getMovieId()] ?? null) : null;

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

// Today's showtime chips: only the next few that have not started yet.
$nowTime       = gmdate('H:i:s');
$todayShowings = [];

foreach ($showingRepo->findToday() as $s) {
    if ($s->getTime() > $nowTime) {
        $todayShowings[] = vm_showing_slot($s);

        if (count($todayShowings) >= 6) {
            break;
        }
    }
}

// Poster row: movies that still have upcoming screenings (max 6).
$nowShowing = array_map(
    'vm_movie_card',
    array_slice($movieRepo->findAllWithUpcomingShowings(), 0, 6)
);

// Weekly programme matrix.
$days     = week_days(7);
$schedule = vm_week_schedule($showingRepo->findWeek(7), $movieById, $days);

// Latest news (first one highlighted).
$events = [];

foreach ($newsRepo->findLatest(3) as $i => $n) {
    $events[] = vm_news_card($n, $i === 0);
}

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/sections/hero.php';
?>

<?php if ($company !== null && trim((string) $company['description']) !== ''): ?>
    <section class="max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl">
        <h2 class="text-2xl font-bold mb-4">About our cinema</h2>
        <p class="whitespace-pre-line text-on-surface-variant max-w-3xl"><?= e($company['description']) ?></p>
    </section>
<?php endif; ?>

<?php
require __DIR__ . '/templates/sections/showtimes.php';
require __DIR__ . '/templates/sections/events.php';
require __DIR__ . '/templates/layout/footer.php';
