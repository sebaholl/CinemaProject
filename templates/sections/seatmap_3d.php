<?php
// Full-screen 3D seat-picker page. Standalone document (its own <head>) so the
// immersive Three.js layout takes over the whole viewport instead of sitting inside
// the normal site chrome. Needs: $showing (array) prepared by booking.php.
//
// The page talks to the backend in two places:
//   1. api/seats.php  -> loads live seat availability for the 3D scene
//   2. checkout.php    -> receives the chosen seats when "Continue" is pressed
//
// Seat rules are re-checked server-side at checkout; the JS here is for UX only.

$seatsUrl = url('api/seats.php?showing_id=' . (int) $showing['id']);
$backUrl = url('booking.php?showing_id=' . (int) $showing['id']); // back to the 2D plan
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Book in 3D · <?= e($showing['title']) ?> | <?= e(SITE_NAME) ?></title>

    <link rel="stylesheet" href="<?= e(asset('css/cinema3d.css')) ?>">

    <!-- Map the bare import names used by CinemaScene.js to our local vendored files.
         No build step: the browser resolves these ES modules directly. -->
    <script type="importmap">
    {
      "imports": {
        "three": "<?= e(asset('js/vendor/three.module.js')) ?>",
        "three/addons/": "<?= e(asset('js/vendor/three-addons/')) ?>",
        "gsap": "<?= e(asset('js/vendor/gsap.js')) ?>"
      }
    }
    </script>
</head>

<body>
    <div id="app">
        <main class="experience">
            <div id="scene" class="scene" data-showing-id="<?= (int) $showing['id'] ?>"
                data-seats-url="<?= e($seatsUrl) ?>" aria-label="Interactive 3D cinema seating plan"></div>

            <div class="hud-right">
                <div class="controls">
                    <a class="button button--ghost" href="<?= e($backUrl) ?>">Return to 2D view</a>
                    <button class="button button--ghost" id="reset-view" type="button">Reset view</button>
                </div>

                <aside class="legend" aria-label="Seat availability legend">
                    <span><i class="seat-dot seat-dot--available"></i>Available</span>
                    <span><i class="seat-dot seat-dot--selected"></i>Selected</span>
                    <span><i class="seat-dot seat-dot--occupied"></i>Occupied</span>
                </aside>
            </div>

            <section class="booking" id="booking" aria-label="Ticket booking" aria-live="polite">
                <article class="bk-card">
                    <h2 class="bk-card__title">Tickets</h2>
                    <hr class="bk-divider">
                    <div class="bk-ticket">
                        <div class="bk-ticket__info">
                            <span class="bk-muted"><?= (int) Pricing::STANDARD ?> kr standard incl.
                                <?= (int) Pricing::BOOKING_FEE ?> kr fee</span>
                        </div>
                        <div class="bk-stepper">
                            <button class="bk-step bk-step--minus" id="ticket-minus" type="button"
                                aria-label="Fewer tickets">−</button>
                            <span class="bk-step__count" id="ticket-count" aria-live="polite">2</span>
                            <button class="bk-step bk-step--plus" id="ticket-plus" type="button"
                                aria-label="More tickets">+</button>
                        </div>
                    </div>
                    <p class="bk-muted bk-note">Up to <?= (int) SeatRules::MAX_SEATS ?> seats per booking. Luxury seats
                        cost <?= (int) Pricing::LUXURY ?> kr.</p>
                </article>

                <article class="bk-card">
                    <h2 class="bk-card__title">Your seats</h2>

                    <!-- The form posts the chosen seats to the shared checkout controller. -->
                    <form id="booking-form" method="post" action="<?= e(url('checkout.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="step" value="select">
                        <input type="hidden" name="showing_id" value="<?= (int) $showing['id'] ?>">
                        <input type="hidden" name="seats" id="seats-field" value="">

                        <p class="bk-seats__empty" id="seats-empty">No seats selected yet.</p>
                        <ul class="bk-seats__list" id="seats-list" hidden></ul>
                        <hr class="bk-divider">
                        <div class="bk-total">
                            <span class="bk-total__label">Total</span>
                            <span class="bk-total__value" id="total-value">0 kr</span>
                        </div>
                        <p class="bk-hint" id="seats-hint">Select 2 seats.</p>
                        <button class="bk-checkout" id="checkout" type="submit" disabled>
                            <span>CONTINUE TO CHECKOUT</span>
                        </button>
                    </form>
                </article>
            </section>

            <div class="toast" id="toast" role="status" hidden></div>
        </main>
    </div>

    <script type="module" src="<?= e(asset('js/cinema/booking-3d.js')) ?>"></script>
</body>

</html>