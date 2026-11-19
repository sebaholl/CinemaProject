<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new NewsRepository($pdo);
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
            $_POST['news_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false || $id === null) {
            http_response_code(400);
            $error = 'Invalid article ID.';
        } elseif (!$repository->delete($id)) {
            $error = 'Article not found.';
        } else {
            admin_flash('Article deleted.');
            header('Location: ' . url('admin/news.php'), true, 303);
            exit;
        }
    }
}

$articles = $repository->findAllForAdmin();
$today = gmdate('Y-m-d');
$flash = admin_take_flash();
$pageTitle = 'Manage news';
$adminPage = 'news';

require __DIR__ . '/../templates/admin/header.php';
?>
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <h1 class="text-3xl font-bold">News</h1>
    <a href="<?= e(url('admin/news_edit.php')) ?>"
       class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        Add article
    </a>
</div>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<p class="mb-5 text-on-surface-variant">
    Future-dated articles are scheduled and hidden from public pages.
</p>

<?php if ($articles === []): ?>
    <p>No articles yet.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-xl bg-surface-container-low">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="p-4">Title</th>
                    <th class="p-4">Category</th>
                    <th class="p-4">Publish date (UTC)</th>
                    <th class="p-4">Author</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr class="border-b border-white/10">
                        <td class="p-4"><?= e($article->getTitle()) ?></td>
                        <td class="p-4"><?= e($article->getCategory()) ?></td>
                        <td class="p-4">
                            <?= e($article->getPublicationDate()) ?>
                        </td>
                        <td class="p-4">
                            <?= e($article->getAuthorName() ?? 'Cinema team') ?>
                        </td>
                        <td class="p-4">
                            <?= $article->getPublicationDate() > $today
                                ? 'Scheduled'
                                : 'Published' ?>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-4">
                                <a class="underline"
                                   href="<?= e(url(
                                       'admin/news_edit.php?id=' .
                                       $article->getId()
                                   )) ?>">
                                    Edit
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Delete this article?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action"
                                           value="delete">
                                    <input type="hidden" name="news_id"
                                           value="<?= $article->getId() ?>">
                                    <button type="submit"
                                            class="text-red-400 hover:underline">
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