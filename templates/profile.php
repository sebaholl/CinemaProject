<section class="w-full max-w-[720px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <h1 class="font-headline-lg text-headline-lg text-on-surface">
        My profile
    </h1>
    <?php if ($error !== null): ?>
    <p role="alert" class="mt-space-md text-error"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($notice !== null): ?>
    <p role="status" class="mt-space-md text-on-surface">
        <?= e($notice) ?>
    </p>
<?php endif; ?>

    <dl class="mt-space-lg flex flex-col gap-space-md">
        <div>
            <dt class="text-on-surface-variant">Name</dt>
            <dd class="text-on-surface">
                <?= e($customer['first_name']) ?>
                <?= e($customer['last_name']) ?>
            </dd>
        </div>

        <div>
            <dt class="text-on-surface-variant">Email</dt>
            <dd class="text-on-surface"><?= e($customer['email']) ?></dd>
        </div>

        <div>
            <dt class="text-on-surface-variant">Member since</dt>
            <dd class="text-on-surface">
                <?= e(substr((string) $customer['created_at'], 0, 10)) ?>
            </dd>
        </div>
    </dl>

<h2 class="mt-space-xl font-headline-lg text-subheading text-on-surface">
    Edit account details
</h2>

<form method="post"
      action="<?= e(url('profile.php')) ?>"
      class="mt-space-md flex flex-col gap-space-md">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="details">

    <label class="flex flex-col gap-space-xs">
        First name
        <input name="first_name"
               required
               maxlength="100"
               value="<?= e($customer['first_name']) ?>">
    </label>

    <label class="flex flex-col gap-space-xs">
        Last name
        <input name="last_name"
               required
               maxlength="100"
               value="<?= e($customer['last_name']) ?>">
    </label>

    <label class="flex flex-col gap-space-xs">
        Email
        <input type="email"
               name="email"
               required
               maxlength="254"
               value="<?= e($customer['email']) ?>">
    </label>

    <button type="submit"
            class="px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary">
        Save details
    </button>
</form>

<h2 class="mt-space-xl font-headline-lg text-subheading text-on-surface">
    Change password
</h2>

<form method="post"
      action="<?= e(url('profile.php')) ?>"
      class="mt-space-md flex flex-col gap-space-md">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="password">

    <label class="flex flex-col gap-space-xs">
        Current password
        <input type="password"
               name="current_password"
               required
               autocomplete="current-password">
    </label>

    <label class="flex flex-col gap-space-xs">
        New password
        <input type="password"
               name="new_password"
               required
               minlength="12"
               autocomplete="new-password">
    </label>

    <label class="flex flex-col gap-space-xs">
        Confirm new password
        <input type="password"
               name="confirm_password"
               required
               autocomplete="new-password">
    </label>

    <button type="submit"
            class="px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary">
        Change password
    </button>
</form>


<h2 class="mt-space-xl font-headline-lg text-subheading text-on-surface">
    My bookings
</h2>

<?php if ($bookings === []): ?>
    <p class="mt-space-md text-on-surface-variant">
        You have no bookings yet.
    </p>
<?php endif; ?>

<?php foreach ($bookings as $booking): ?>
    <article class="mt-space-md p-space-md rounded-xl bg-surface-container-low">
        <h3 class="font-body-bold text-on-surface">
            <?= e($booking['movie_title']) ?>
        </h3>

        <p>
            <?= e($booking['screening_date']) ?>
            at <?= e(substr((string) $booking['screening_time'], 0, 5)) ?>
            · <?= e($booking['hall_name']) ?>
        </p>

        <p>
            Seats:
            <?php if ($booking['status'] === 'cancelled'): ?>
                Released
            <?php else: ?>
                <?= e($booking['seat_labels'] ?: 'No seats recorded') ?>
            <?php endif; ?>
        </p>

        <p>
            Total:
            <?= e($booking['invoice_total'] ?? $booking['price']) ?> kr
            · Status: <?= e($booking['status']) ?>
        </p>

        <?php if ($booking['invoice_id'] !== null): ?>
            <p>Invoice #<?= (int) $booking['invoice_id'] ?></p>
        <?php endif; ?>
    </article>
<?php endforeach; ?>

</section>

<form method="post"
      action="<?= e(url('logout.php')) ?>"
      class="mt-space-lg">
    <?= csrf_field() ?>

    <button type="submit"
            class="px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary">
        Log out
    </button>
</form>