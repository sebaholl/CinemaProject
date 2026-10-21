<?php
// Page layout: bottom part.
// TODO: the original footer was cut off in the pasted design. This is a simple placeholder.
?>
</main>

<footer class="w-full bg-surface-container-lowest border-t border-surface-container-highest/30">
    <div class="w-full max-w-[1440px] mx-auto px-margin-mobile md:px-margin py-space-xl flex flex-col md:flex-row items-center justify-between gap-space-md text-on-surface-variant font-caption-label text-caption-label">
        <span>&copy; <?= e(date('Y')) ?> <?= e(SITE_NAME) ?></span>
        <nav class="flex items-center gap-space-lg">
            <a class="hover:text-on-surface transition-colors" href="<?= e(url('movies.php')) ?>">Movies</a>
            <a class="hover:text-on-surface transition-colors" href="<?= e(url('news.php')) ?>">News</a>
            <a class="hover:text-on-surface transition-colors" href="<?= e(url('contact.php')) ?>">Contact</a>
        </nav>
    </div>
</footer>

<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
