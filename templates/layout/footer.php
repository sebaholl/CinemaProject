<?php
// Shared footer for public pages.
$footerContacts = (new ContactInfoRepository(
    Database::getInstance()->getConnection()
))->findAll();
?>
</main>

<footer class="w-full bg-surface-container-lowest border-t border-surface-container-highest/30">
    <div class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl flex flex-col md:flex-row items-center justify-between gap-space-md text-on-surface-variant font-caption-label text-caption-label">
        <span>&copy; <?= e(date('Y')) ?> <?= e(SITE_NAME) ?></span>

        <nav class="flex items-center gap-space-lg">
            <a class="hover:text-on-surface transition-colors"
               href="<?= e(url('movies.php')) ?>">Movies</a>
            <a class="hover:text-on-surface transition-colors"
               href="<?= e(url('news.php')) ?>">News</a>
            <a class="hover:text-on-surface transition-colors"
               href="<?= e(url('contact.php')) ?>">Contact</a>
        </nav>
    </div>

    <?php if ($footerContacts !== []): ?>
        <div class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin pb-space-xl flex flex-wrap gap-space-lg text-on-surface-variant font-caption-label text-caption-label">
            <?php foreach ($footerContacts as $contact): ?>
                <p>
                    <strong><?= e($contact['label']) ?>:</strong>
                    <?= e($contact['value']) ?>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</footer>

<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
