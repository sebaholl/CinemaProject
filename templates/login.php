<section class="w-full max-w-[480px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface">
        Log in
    </h1>

    <?php if ($error !== null): ?>
        <p role="alert" class="mt-space-md text-error">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <form method="post"
          action="<?= e(url('login.php')) ?>"
          class="mt-space-lg flex flex-col gap-space-md">

        <?= csrf_field() ?>

        <input type="hidden"
               name="return"
               value="<?= e($returnTo) ?>">

        <label class="flex flex-col gap-space-xs">
            <span>Email or admin login</span>
            <input type="text"
                   name="identifier"
                   value="<?= e($identifier) ?>"
                   required
                   autocomplete="username"
                   class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
        </label>

        <label class="flex flex-col gap-space-xs">
            <span>Password</span>
            <input type="password"
                   name="password"
                   required
                   autocomplete="current-password"
                   class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
        </label>

        <button type="submit"
                class="px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary">
            Log in
        </button>
    </form>
</section>