<?php
// Movie list page. Reads every movie from the database.
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Movies';
$currentPage = 'movies';

$pdo    = Database::getInstance()->getConnection();
$movies = array_map('vm_movie_card', (new MovieRepository($pdo))->findAll());

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight pb-space-lg">Movies</h1>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-space-md">
        <?php foreach ($movies as $movie): ?>
            <?php $isActive = false; include __DIR__ . '/templates/partials/movie_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
