<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$repository = new NewsRepository($pdo);
$currentPage = 'news';

if (isset($_GET['id'])) {
    $id = filter_var(
        $_GET['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        http_response_code(404);
        exit('Article not found.');
    }

    $article = $repository->findById($id);

    if ($article === null) {
        http_response_code(404);
        exit('Article not found.');
    }

    $pageTitle = $article->getTitle();

    require __DIR__ . '/templates/layout/header.php';
    ?>
    <article class="w-full max-w-[820px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
        <a href="<?= e(url('news.php')) ?>" class="underline">
            Back to news
        </a>

        <p class="mt-8 text-on-surface-variant">
            <?= e($article->getCategory()) ?>
            · <?= e($article->getPublicationDate()) ?>
            · By <?= e($article->getAuthorName() ?? 'Cinema team') ?>
        </p>

        <h1 class="mt-3 text-4xl font-bold">
            <?= e($article->getTitle()) ?>
        </h1>

        <?php if ($article->getImage() !== null): ?>
            <img src="<?= e($article->getImage()) ?>"
                 alt=""
                 class="mt-8 w-full rounded-xl">
        <?php endif; ?>

        <p class="mt-8 leading-relaxed text-on-surface-variant"><?= nl2br(e($article->getDescription())) ?></p>
    </article>
    <?php
    require __DIR__ . '/templates/layout/footer.php';
    exit;
}

$pageTitle = 'News';
$events = [];

foreach ($repository->findAll() as $index => $article) {
    $events[] = vm_news_card($article, $index === 0);
}

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight pb-space-lg">
        News
    </h1>

    <?php if ($events === []): ?>
        <p>No published news yet.</p>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
        <?php foreach ($events as $event): ?>
            <?php include __DIR__ . '/templates/partials/event_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>