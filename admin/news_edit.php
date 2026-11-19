<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new NewsRepository($pdo);
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
        exit('Invalid article ID.');
    }
}

$existing = $id === null ? null : $repository->findForAdmin($id);

if ($id !== null && $existing === null) {
    http_response_code(404);
    exit('Article not found.');
}

$form = [
    'title' => $existing?->getTitle() ?? '',
    'category' => $existing?->getCategory() ?? 'News',
    'description' => $existing?->getDescription() ?? '',
    'publication_date' => $existing?->getPublicationDate()
        ?? gmdate('Y-m-d'),
    'image' => $existing?->getImage() ?? '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        foreach (array_keys($form) as $key) {
            $value = $_POST[$key] ?? null;
            $form[$key] = is_string($value) ? trim($value) : '';
        }

        $parsedDate = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $form['publication_date'],
            new DateTimeZone('UTC')
        );
        $validDate = $parsedDate !== false
            && $parsedDate->format('Y-m-d')
                === $form['publication_date'];

        $image = $form['image'];
        $validImage = $image === ''
            || (
                strlen($image) <= 500
                && filter_var($image, FILTER_VALIDATE_URL) !== false
                && parse_url($image, PHP_URL_SCHEME) === 'https'
                && parse_url($image, PHP_URL_USER) === null
                && parse_url($image, PHP_URL_PASS) === null
            );

        if ($form['title'] === ''
            || mb_strlen($form['title']) > 200
            || $form['category'] === ''
            || mb_strlen($form['category']) > 80
            || $form['description'] === ''
            || mb_strlen($form['description']) > 10000
            || !$validDate
            || !$validImage) {
            $error = 'Check the title, category, body, publish date and HTTPS image URL.';
        } else {
            $article = [
                'title' => $form['title'],
                'category' => $form['category'],
                'description' => $form['description'],
                'publication_date' => $form['publication_date'],
                'image' => $image === '' ? null : $image,
            ];

            if ($id === null) {
                $repository->create($article, (int) $adminUser['id']);
                admin_flash('Article created.');
            } else {
                $repository->update($id, $article);
                admin_flash('Article updated.');
            }

            header('Location: ' . url('admin/news.php'), true, 303);
            exit;
        }
    }
}

$flash = admin_take_flash();
$pageTitle = $id === null ? 'Add article' : 'Edit article';
$adminPage = 'news';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-6"><?= e($pageTitle) ?></h1>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<p class="mb-5 text-on-surface-variant">
    Author: <?= e($existing?->getAuthorName()
        ?? $adminUser['name']) ?>
</p>

<form method="post"
      action="<?= e(url(
          'admin/news_edit.php' . ($id === null ? '' : '?id=' . $id)
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
        Category
        <input name="category" required maxlength="80"
               value="<?= e($form['category']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Body
        <textarea name="description" required maxlength="10000" rows="10"
                  class="block w-full rounded-xl bg-surface-container-low p-3"><?= e($form['description']) ?></textarea>
    </label>

    <label class="block">
        Publish date (UTC)
        <input type="date" name="publication_date" required
               value="<?= e($form['publication_date']) ?>"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <label class="block">
        Image HTTPS URL (optional)
        <input type="url" name="image" maxlength="500"
               value="<?= e($form['image']) ?>"
               placeholder="https://example.com/image.jpg"
               class="block w-full rounded-xl bg-surface-container-low p-3">
    </label>

    <div class="flex flex-wrap gap-4">
        <button type="submit"
                class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
            <?= $id === null ? 'Create article' : 'Save article' ?>
        </button>
        <a href="<?= e(url('admin/news.php')) ?>"
           class="rounded-xl bg-surface-container-high px-5 py-3">
            Cancel
        </a>
    </div>
</form>
<?php require __DIR__ . '/../templates/admin/footer.php'; ?>