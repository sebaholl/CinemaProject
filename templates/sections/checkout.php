<?php
// Mock card form + order summary. Presentation only.
// Needs: $showing (array), $orderSeats (array of ['label','price']), $total (int),
//        $cardError (string|null). Rendered by checkout.php (step=select / failed pay).
$cardError = $cardError ?? null;
?>
<section class="w-full max-w-[880px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">
    <a href="<?= e(url('booking.php?showing_id=' . (int) $showing['id'])) ?>" class="inline-flex items-center gap-space-xs text-on-surface-variant hover:text-on-surface transition-colors font-caption-label text-caption-label">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to seat selection
    </a>

    <h1 class="mt-space-sm font-headline-lg text-headline-lg text-on-surface tracking-tight">Checkout</h1>
    <p class="mt-space-xs font-caption-label text-caption-label text-on-surface-variant">
        <?= e($showing['title']) ?> · <?= e($showing['date_label']) ?> · <?= e($showing['time']) ?> · Screen <?= (int) $showing['screen'] ?>
    </p>

    <div class="mt-space-lg grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">

        <!-- Order summary -->
        <div class="lg:col-span-5 p-space-lg rounded-xl bg-surface-container-low border border-surface-container-highest/30">
            <h2 class="font-headline-lg text-subheading text-on-surface tracking-tight">Your order</h2>
            <ul class="mt-space-md flex flex-col gap-space-xs">
                <?php foreach ($orderSeats as $seat): ?>
                    <li class="flex items-center justify-between font-caption-label text-caption-label text-on-surface">
                        <span>Seat <?= e($seat['label']) ?></span>
                        <span><?= (int) $seat['price'] ?> kr</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-space-md flex items-center justify-between border-t border-surface-container-highest/30 pt-space-md">
                <span class="font-body-bold text-body-bold text-on-surface">Total</span>
                <span class="font-headline-lg text-subheading text-on-surface"><?= (int) $total ?> kr</span>
            </div>
            <p class="mt-space-sm font-caption-label text-caption-label text-on-surface-variant"><?= count($orderSeats) ?> seat<?= count($orderSeats) === 1 ? '' : 's' ?> · includes booking fee</p>
        </div>

        <!-- Fake card form -->
        <div class="lg:col-span-7 p-space-lg rounded-xl bg-surface-container-low border border-surface-container-highest/30">
            <h2 class="font-headline-lg text-subheading text-on-surface tracking-tight">Payment</h2>
            <p class="mt-space-xs font-caption-label text-caption-label text-on-surface-variant">
                This is a mock checkout — no real payment is taken and no card details are stored.
            </p>

            <?php if ($cardError !== null): ?>
                <div class="mt-space-md p-space-md rounded-xl bg-error-container/20 border border-error/40">
                    <p class="font-caption-label text-caption-label text-error flex items-center gap-space-xs">
                        <span class="material-symbols-outlined text-[18px]">error</span>
                        <?= e($cardError) ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('checkout.php')) ?>" class="mt-space-md flex flex-col gap-space-md" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="step" value="pay">

                <label class="flex flex-col gap-space-xs">
                    <span class="font-caption-label text-caption-label text-on-surface-variant">Cardholder name</span>
                    <input type="text" name="card_name" required
                           class="w-full px-space-md py-space-sm rounded-xl bg-surface-container-high border border-surface-container-highest/30 text-on-surface focus:outline-none focus:border-primary"
                           placeholder="Alex Demo">
                </label>

                <label class="flex flex-col gap-space-xs">
                    <span class="font-caption-label text-caption-label text-on-surface-variant">Card number</span>
                    <input type="text" name="card_number" inputmode="numeric" required
                           class="w-full px-space-md py-space-sm rounded-xl bg-surface-container-high border border-surface-container-highest/30 text-on-surface focus:outline-none focus:border-primary"
                           placeholder="4111 1111 1111 1111">
                </label>

                <div class="flex gap-space-md">
                    <label class="flex-1 flex flex-col gap-space-xs">
                        <span class="font-caption-label text-caption-label text-on-surface-variant">Expiry</span>
                        <input type="text" name="card_expiry" required
                               class="w-full px-space-md py-space-sm rounded-xl bg-surface-container-high border border-surface-container-highest/30 text-on-surface focus:outline-none focus:border-primary"
                               placeholder="12/29">
                    </label>
                    <label class="flex-1 flex flex-col gap-space-xs">
                        <span class="font-caption-label text-caption-label text-on-surface-variant">CVC</span>
                        <input type="text" name="card_cvc" inputmode="numeric" required
                               class="w-full px-space-md py-space-sm rounded-xl bg-surface-container-high border border-surface-container-highest/30 text-on-surface focus:outline-none focus:border-primary"
                               placeholder="123">
                    </label>
                </div>

                <p class="font-caption-label text-[11px] text-on-surface-variant/70">Demo tip: any card works except one ending in 0000, which simulates a decline.</p>

                <button type="submit"
                        class="mt-space-xs w-full inline-flex items-center justify-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                    Pay <?= (int) $total ?> kr
                </button>
            </form>
        </div>
    </div>
</section>
