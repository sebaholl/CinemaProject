<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Movies';
$currentPage = 'movies';

$pdo = Database::getInstance()->getConnection();
$repository = new MovieRepository($pdo);

$movies = array_map('vm_movie_card', $repository->findAll());
$detailsById = [];

foreach ($repository->findAllDetails() as $details) {
    $detailsById[(int) $details['movie_id']] = $details;
}

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight pb-space-lg">
        Movies
    </h1>

    <?php if ($movies === []): ?>
        <p>No movies are listed yet.</p>
    <?php endif; ?>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-space-md">
        <?php foreach ($movies as $movie): ?>
            <?php $details = $detailsById[(int) $movie['id']]; ?>

            <div>
                <?php
                $isActive = false;
                include __DIR__ . '/templates/partials/movie_card.php';
                ?>

                <p class="mt-2 text-sm text-on-surface-variant">
                    <?= e($details['age_rating']) ?>
                    <?php if ($details['release_date'] !== null): ?>
                        · Released <?= e($details['release_date']) ?>
                    <?php endif; ?>
                </p>

                <p class="mt-2 text-sm text-on-surface-variant">
                    <?= e($details['description']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
