<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new MovieRepository($pdo);
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } elseif (($_POST['action'] ?? null) !== 'delete') {
        http_response_code(400);
        $error = 'Unknown action.';
    } else {
        $id = filter_var(
            $_POST['movie_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false || $id === null) {
            http_response_code(400);
            $error = 'Invalid movie ID.';
        } else {
            try {
                if (!$repository->delete($id)) {
                    $error = 'Movie not found.';
                } else {
                    admin_flash('Movie deleted.');
                    header('Location: ' . url('admin/movies.php'), true, 303);
                    exit;
                }
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}

$movies = $repository->findAllDetails();
$flash = admin_take_flash();
$pageTitle = 'Manage movies';
$adminPage = 'movies';

require __DIR__ . '/../templates/admin/header.php';
?>
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <h1 class="text-3xl font-bold">Movies</h1>
    <a href="<?= e(url('admin/movie_edit.php')) ?>"
       class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        Add movie
    </a>
</div>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<p class="mb-5 text-on-surface-variant">
    Movies with showings cannot be deleted. Edit their details instead.
</p>

<?php if ($movies === []): ?>
    <p>No movies yet.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-xl bg-surface-container-low">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="p-4">Movie</th>
                    <th class="p-4">Genre</th>
                    <th class="p-4">Duration</th>
                    <th class="p-4">Age rating</th>
                    <th class="p-4">Release date</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movies as $movie): ?>
                    <tr class="border-b border-white/10">
                        <td class="p-4"><?= e($movie['title']) ?></td>
                        <td class="p-4"><?= e($movie['genre']) ?></td>
                        <td class="p-4">
                            <?= (int) $movie['duration_minutes'] ?> min
                        </td>
                        <td class="p-4"><?= e($movie['age_rating']) ?></td>
                        <td class="p-4">
                            <?= $movie['release_date'] === null
                                ? '—'
                                : e($movie['release_date']) ?>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap items-center gap-4">
                                <a class="underline"
                                   href="<?= e(url(
                                       'admin/movie_edit.php?id=' .
                                       (int) $movie['movie_id']
                                   )) ?>">
                                    Edit
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Delete this movie?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="movie_id"
                                           value="<?= (int) $movie['movie_id'] ?>">
                                    <button class="text-red-400 hover:underline"
                                            type="submit">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>