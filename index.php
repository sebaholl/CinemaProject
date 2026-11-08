<?php
// Front page: hero, weekly showtimes, events/news.
// This file is the "controller": it reads data through the repositories and
// prepares the arrays the templates need, then includes the templates.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Home';
$currentPage = 'home';

$pdo         = Database::getInstance()->getConnection();
$movieRepo   = new MovieRepository($pdo);
$showingRepo = new ShowingRepository($pdo);
$newsRepo    = new NewsRepository($pdo);

// Movie lookup (id => Movie) so the weekly matrix can get posters/durations cheaply.
$movieById = [];
foreach ($movieRepo->findAll() as $m) {
    $movieById[$m->getId()] = $m;
}

// Featured movie for the hero: the one flagged is_featured in the DB.
// Fallbacks keep the hero populated if nothing is flagged: soonest upcoming, then first movie.
$featuredMovie = $movieRepo->findFeatured();
if ($featuredMovie === null) {
    $upcoming      = $showingRepo->findUpcoming();
    $featuredMovie = $upcoming ? ($movieById[$upcoming[0]->getMovieId()] ?? null) : null;
}
if ($featuredMovie === null) {
    $featuredMovie = $movieById ? reset($movieById) : null;
}
$featured = $featuredMovie
    ? [
        'title'       => $featuredMovie->getTitle(),
        'duration'    => $featuredMovie->getDurationMinutes(),
        'description' => $featuredMovie->getDescription(),
        'backdrop'    => $featuredMovie->getBackdropImage(),
      ]
    : ['title' => SITE_NAME, 'duration' => 0, 'description' => '', 'backdrop' => null];

// Today's showtime chips: only the next few that have not started yet (keeps the hero tidy).
$nowTime       = gmdate('H:i:s');
$todayShowings = [];
foreach ($showingRepo->findToday() as $s) {
    if ($s->getTime() > $nowTime) {
        $todayShowings[] = vm_showing_slot($s);
        if (count($todayShowings) >= 6) { break; }
    }
}

// Poster row: movies that still have upcoming screenings (max 6).
$nowShowing = array_map('vm_movie_card', array_slice($movieRepo->findAllWithUpcomingShowings(), 0, 6));

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
require __DIR__ . '/templates/sections/showtimes.php';
require __DIR__ . '/templates/sections/events.php';
require __DIR__ . '/templates/layout/footer.php';
