<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new MovieRepository($pdo);
$error = null;

$rawId = $_GET['id'] ?? null;
$id = null;

if ($rawId !== null) {
    $id = filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        exit('Invalid movie ID.');
    }
}

$existing = $id === null ? null : $repository->findDetailsById($id);

if ($id !== null && $existing === null) {
    http_response_code(404);
    exit('Movie not found.');
}

$form = [
    'title' => (string) ($existing['title'] ?? ''),
    'genre' => (string) ($existing['genre'] ?? ''),
    'description' => (string) ($existing['description'] ?? ''),
    'duration_minutes' => (string) ($existing['duration_minutes'] ?? ''),
    'age_rating' => (string) ($existing['age_rating'] ?? 'Not rated'),
    'release_date' => (string) ($existing['release_date'] ?? ''),
    'cover_image' => (string) ($existing['cover_image'] ?? ''),
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        foreach (array_keys($form) as $field) {
            $value = $_POST[$field] ?? null;
            $form[$field] = is_string($value) ? trim($value) : '';
        }

        $duration = filter_var(
            $form['duration_minutes'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 600]]
        );

        $releaseDate = $form['release_date'];
        $validReleaseDate = $releaseDate === '';

        if (!$validReleaseDate) {
            $parsed = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $releaseDate
            );
            $validReleaseDate = $parsed !== false
                && $parsed->format('Y-m-d') === $releaseDate;
        }

        $poster = $form['cover_image'];
        $validPoster = $poster === ''
            || (
                strlen($poster) <= 500
                && filter_var($poster, FILTER_VALIDATE_URL) !== false
                && parse_url($poster, PHP_URL_SCHEME) === 'https'
                && parse_url($poster, PHP_URL_USER) === null
                && parse_url($poster, PHP_URL_PASS) === null
            );

        if ($form['title'] === ''
            || mb_strlen($form['title']) > 200
            || $form['genre'] === ''
            || mb_strlen($form['genre']) > 100
            || $form['description'] === ''
            || mb_strlen($form['description']) > 5000
            || $duration === false
            || $form['age_rating'] === ''
            || mb_strlen($form['age_rating']) > 20
            || !$validReleaseDate
            || !$validPoster) {
            $error = 'Check the title, genre, description, duration, age rating, release date and HTTPS poster URL.';
        } else {
            $movie = [
                'title' => $form['title'],
                'genre' => $form['genre'],
                'description' => $form['description'],
                'duration_minutes' => $duration,
                'age_rating' => $form['age_rating'],
                'release_date' => $releaseDate === '' ? null : $releaseDate,
                'cover_image' => $poster === '' ? null : $poster,
            ];

            if ($id === null) {
                $repository->create($movie);
                admin_flash('Movie added.');
            } else {
                $repository->update($id, $movie);
                admin_flash('Movie updated.');
            }

            header('Location: ' . url('admin/movies.php'), true, 303);
            exit;
        }
    }
}

$flash = admin_take_flash();
$pageTitle = $id === null ? 'Add movie' : 'Edit movie';
$adminPage = 'movies';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-6"><?= e($pageTitle) ?></h1>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<form method="post"
      action="<?= e(url(
          'admin/movie_edit.php' . ($id === null ? '' : '?id=' . $id)
      )) ?>"
      class="max-w-3xl space-y-5">
    <?= csrf_field() ?>

    <label class="block">
        Title
        <input name="title" required maxlength="200"
               value="<?= e($form['title']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Genre
        <input name="genre" required maxlength="100"
               value="<?= e($form['genre']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Description
        <textarea name="description" required maxlength="5000" rows="6"
                  class="block w-full rounded-xl bg-surface-container-low p-3"><?= e($form['description']) ?></textarea>
    </label>

    <label class="block">
        Duration in minutes
        <input type="number" name="duration_minutes"
               required min="1" max="600"
               value="<?= e($form['duration_minutes']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Age rating
        <input name="age_rating" required maxlength="20"
               placeholder="For example: PG-13"
               value="<?= e($form['age_rating']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Release date
        <input type="date" name="release_date"
               value="<?= e($form['release_date']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Poster HTTPS URL (optional)
        <input type="url" name="cover_image" maxlength="500"
               placeholder="https://example.com/poster.jpg"
               value="<?= e($form['cover_image']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <div class="flex flex-wrap gap-4">
        <button type="submit"
                class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
            <?= $id === null ? 'Add movie' : 'Save movie' ?>
        </button>

        <a href="<?= e(url('admin/movies.php')) ?>"
           class="rounded-xl bg-surface-container-high px-5 py-3">
            Cancel
        </a>
    </div>
</form>
<?php require __DIR__ . '/../templates/admin/footer.php'; ?>