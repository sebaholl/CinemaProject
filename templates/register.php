<section class="w-full max-w-[480px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface">
        Create an account
    </h1>

    <?php if ($success): ?>
        <p role="status" class="mt-space-md text-on-surface">
            Account created. You can now log in.
        </p>
        <a href="<?= e(url('login.php')) ?>" class="mt-space-md inline-block text-primary hover:underline">
            Go to log in
        </a>
    <?php else: ?>
        <?php if ($error !== null): ?>
            <p role="alert" class="mt-space-md text-error">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <form method="post" action="<?= e(url('register.php')) ?>" class="mt-space-lg flex flex-col gap-space-md">
            <?= csrf_field() ?>

            <label class="flex flex-col gap-space-xs">
                <span>First name</span>
                <input name="first_name" required maxlength="100" autocomplete="given-name" value="<?= e($firstName) ?>"
                    class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
            </label>

            <label class="flex flex-col gap-space-xs">
                <span>Last name</span>
                <input name="last_name" required maxlength="100" autocomplete="family-name" value="<?= e($lastName) ?>"
                    class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
            </label>

            <label class="flex flex-col gap-space-xs">
                <span>Email </span>
                <input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($email) ?>"
                    class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
            </label>

            <label class="flex flex-col gap-space-xs">
                <span>Password </span>
                <input type="password" name="password" required minlength="12" autocomplete="new-password"
                    class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
            </label>

            <label class="flex flex-col gap-space-xs">
                <span>Confirm password</span>
                <input type="password" name="confirm_password" required autocomplete="new-password"
                    class="px-space-md py-space-sm rounded-xl bg-surface-container-high text-on-surface">
            </label>

            <button type="submit" class="px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary">
                Create account
            </button>
        </form>

        <p class="mt-space-md">
            Already registered?
            <a href="<?= e(url('login.php')) ?>" class="text-primary hover:underline">
                Log in
            </a>
        </p>
    <?php endif; ?>
</section>