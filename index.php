<?php
// Front page: hero, weekly showtimes, events/news.
// This file only prepares the data and picks which templates to show.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Home';
$currentPage = 'home';

// TEMPORARY sample data. Later replaced by Movie / Showing / News classes reading the database.
$featured      = SampleData::featuredMovie();
$todayShowings = SampleData::todayShowings();
$nowShowing    = SampleData::nowShowing();
$days          = SampleData::weekDays();
$schedule      = SampleData::weekSchedule();
$events        = SampleData::events();

require __DIR__ . '/templates/layout/header.php';
require __DIR__ . '/templates/sections/hero.php';
require __DIR__ . '/templates/sections/showtimes.php';
require __DIR__ . '/templates/sections/events.php';
require __DIR__ . '/templates/layout/footer.php';
