<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new ShowingRepository($pdo);
$error = null;
$id = null;

if (isset($_GET['id'])) {
    $id = filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(400);
        exit('Invalid showing ID.');
    }
}

$existing = $id === null ? null : $repository->details($id);

if ($id !== null && $existing === null) {
    http_response_code(404);
    exit('Showing not found.');
}

$form = [
    'movie_id' => (string) ($existing['movie_id'] ?? ''),
    'hall_id' => (string) ($existing['hall_id'] ?? ''),
    'screening_date' => (string) ($existing['screening_date'] ?? ''),
    'screening_time' => substr(
        (string) ($existing['screening_time'] ?? ''),
        0,
        5
    ),
    'format' => (string) ($existing['format'] ?? '2D, Eng subs'),
    'price_kr' => (string) ($existing['price_kr'] ?? '150'),
];

$movies = $repository->movieOptions();
$halls = $repository->hallOptions();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        foreach (array_keys($form) as $key) {
            $value = $_POST[$key] ?? null;
            $form[$key] = is_string($value) ? trim($value) : '';
        }

        $movieId = filter_var(
            $form['movie_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $hallId = filter_var(
            $form['hall_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $price = filter_var(
            $form['price_kr'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 10000]]
        );

        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $form['screening_date'] . ' ' . $form['screening_time'],
            new DateTimeZone('UTC')
        );
        $validDateTime = $parsed !== false
            && $parsed->format('Y-m-d H:i')
                === $form['screening_date'] . ' ' . $form['screening_time'];

        if ($movieId === false
            || $hallId === false
            || $price === false
            || !$validDateTime
            || $form['format'] === ''
            || mb_strlen($form['format']) > 60) {
            $error = 'Check movie, auditorium, date, time, format and price.';
        } else {
            try {
                $repository->save($id, [
                    'movie_id' => $movieId,
                    'hall_id' => $hallId,
                    'screening_date' => $form['screening_date'],
                    'screening_time' => $form['screening_time'],
                    'format' => $form['format'],
                    'price_kr' => $price,
                ]);

                admin_flash($id === null
                    ? 'Showing created.'
                    : 'Showing updated.');
                header('Location: ' . url('admin/showings.php'), true, 303);
                exit;
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}

$flash = admin_take_flash();
$pageTitle = $id === null ? 'Add showing' : 'Edit showing';
$adminPage = 'showings';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-5"><?= e($pageTitle) ?></h1>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<form method="post"
      action="<?= e(url(
          'admin/showing_edit.php' . ($id === null ? '' : '?id=' . $id)
      )) ?>"
      class="max-w-2xl space-y-5">
    <?= csrf_field() ?>

    <label class="block">
        Movie
        <select name="movie_id" required
                class="block w-full rounded-xl bg-surface-container-low p-3">
            <option value="">Choose a movie</option>
            <?php foreach ($movies as $movie): ?>
                <option value="<?= (int) $movie['movie_id'] ?>"
                    <?= $form['movie_id'] === (string) $movie['movie_id']
                        ? 'selected'
                        : '' ?>>
                    <?= e($movie['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        Auditorium
        <select name="hall_id" required
                class="block w-full rounded-xl bg-surface-container-low p-3">
            <option value="">Choose an auditorium</option>
            <?php foreach ($halls as $hall): ?>
                <option value="<?= (int) $hall['hall_id'] ?>"
                    <?= $form['hall_id'] === (string) $hall['hall_id']
                        ? 'selected'
                        : '' ?>>
                    <?= e($hall['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="block">
        Date (UTC)
        <input type="date" name="screening_date" required
               value="<?= e($form['screening_date']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Start time (UTC)
        <input type="time" name="screening_time" required
               value="<?= e($form['screening_time']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Format
        <input name="format" required maxlength="60"
               value="<?= e($form['format']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Standard ticket price (kr)
        <input type="number" name="price_kr" required min="0" max="10000"
               value="<?= e($form['price_kr']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <button class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        <?= $id === null ? 'Create showing' : 'Save showing' ?>
    </button>
</form>
<?php require __DIR__ . '/../templates/admin/footer.php'; ?>