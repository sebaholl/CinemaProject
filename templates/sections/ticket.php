<?php
/**
 * The ticket as shown on a normal page. Presentation only.
 * Expects: $ticket (Ticket), and optionally $mailNotice (string|null).
 *
 * Same Ticket object as the email, different look: here we can use the site's
 * Tailwind classes, where the email had to fall back to tables and inline styles.
 * One set of data, two presentations.
 */
$poster = $ticket->getPosterUrl();
$qrSrc  = QrCode::imageUrl($ticket->getTicketCode(), 240);
?>
<section class="w-full max-w-[860px] mx-auto px-margin-mobile md:px-margin pt-space-xl pb-space-xl">

    <div class="text-center">
        <p class="font-caption-label text-caption-label uppercase tracking-widest text-on-surface-variant">
            Thank you for your purchase
        </p>
        <h1 class="mt-space-xs font-headline-lg text-headline-lg text-on-surface tracking-tight">
            <?= e($ticket->getMovieTitle()) ?>
        </h1>
        <p class="mt-space-sm font-body-regular text-body-regular text-on-surface-variant">
            Show the QR code below when you arrive at the cinema.
        </p>
    </div>

    <div class="mt-space-lg flex flex-col md:flex-row items-stretch gap-space-md">

        <?php if ($poster !== null && $poster !== ''): ?>
            <div class="md:w-[240px] shrink-0">
                <img src="<?= e($poster) ?>"
                     alt="<?= e($ticket->getMovieTitle()) ?> poster"
                     class="w-full h-full max-h-[420px] object-cover rounded-xl">
            </div>
        <?php endif; ?>

        <!-- The ticket itself -->
        <div class="flex-1 rounded-xl bg-primary-container p-space-lg">

            <div class="flex justify-center">
                <!-- White card behind the QR: scanners need the light quiet zone. -->
                <div class="bg-white rounded-xl p-space-sm">
                    <img src="<?= e($qrSrc) ?>"
                         alt="QR code for ticket <?= e($ticket->getTicketCode()) ?>"
                         width="200" height="200"
                         class="block w-[200px] h-[200px]">
                </div>
            </div>

            <p class="mt-space-md text-center font-mono text-[17px] font-bold tracking-[2px] text-on-primary">
                <?= e($ticket->getTicketCode()) ?>
            </p>

            <dl class="mt-space-md border-t border-on-primary/25 pt-space-md flex flex-col gap-space-xs">
                <div class="flex items-baseline justify-between gap-space-md">
                    <dt class="font-caption-label text-caption-label text-on-primary/80">Ticket</dt>
                    <dd class="font-body-bold text-body-bold text-on-primary text-right">
                        Valid for <?= (int) $ticket->getSeatCount() ?>
                        <?= $ticket->getSeatCount() === 1 ? 'ticket' : 'tickets' ?>
                    </dd>
                </div>
                <?php
                $faceRows = [
                    'Cinema' => SITE_NAME,
                    'Hall'   => $ticket->getHallName(),
                    'Row'    => $ticket->getRowList(),
                    'Seats'  => $ticket->getSeatList(),
                    'Date'   => $ticket->getDateLabel(),
                    'Time'   => $ticket->getScreeningTime(),
                ];
                foreach ($faceRows as $dt => $dd): ?>
                    <div class="flex items-baseline justify-between gap-space-md">
                        <dt class="font-caption-label text-caption-label text-on-primary/80"><?= e($dt) ?></dt>
                        <dd class="font-body-bold text-body-bold text-on-primary text-right"><?= e($dd) ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>

    <!-- Receipt -->
    <div class="mt-space-md rounded-xl bg-surface-container-low p-space-lg">
        <h2 class="font-headline-lg text-subheading text-on-surface">Receipt</h2>

        <dl class="mt-space-md flex flex-col gap-space-xs">
            <?php
            $receiptRows = [
                'Ticket ID' => $ticket->getTicketCode(),
                'Booking'   => '#' . $ticket->getBookingId(),
            ];
            if ($ticket->getInvoiceId() !== null) {
                $receiptRows['Invoice'] = '#' . $ticket->getInvoiceId();
            }
            $receiptRows['Format']    = $ticket->getFormat();
            $receiptRows['Purchased'] = $ticket->getBookedAtLabel();
            foreach ($receiptRows as $dt => $dd): ?>
                <div class="flex items-baseline justify-between gap-space-md">
                    <dt class="text-on-surface-variant"><?= e($dt) ?></dt>
                    <dd class="text-on-surface text-right"><?= e($dd) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="mt-space-md border-t border-surface-container-highest/40 pt-space-md flex flex-col gap-space-xs">
            <?php foreach ($ticket->getSeats() as $seat): ?>
                <div class="flex items-baseline justify-between gap-space-md">
                    <span class="text-on-surface-variant">
                        Seat <?= e($seat['label']) ?>
                        <span class="opacity-70">(<?= e($seat['type']) ?>)</span>
                    </span>
                    <span class="text-on-surface text-right"><?= (int) $seat['price'] ?> kr</span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-space-md border-t border-surface-container-highest/40 pt-space-md flex items-center justify-between">
            <span class="font-body-bold text-body-bold text-on-surface">Total (incl. VAT)</span>
            <span class="font-headline-lg text-subheading text-primary-container">
                <?= (int) $ticket->getTotal() ?> kr
            </span>
        </div>
    </div>

    <?php if (!empty($mailNotice)): ?>
        <p role="status" class="mt-space-md text-center font-body-regular text-body-regular text-on-surface-variant">
            <?= e($mailNotice) ?>
        </p>
    <?php endif; ?>

    <div class="mt-space-lg flex flex-wrap items-center justify-center gap-space-md">
        <?php if ($ticket->getInvoiceId() !== null): ?>
            <a href="<?= e(url('invoice.php?booking=' . $ticket->getBookingId())) ?>"
               class="inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-primary-container text-on-primary font-body-bold text-caption-label uppercase tracking-wider hover:bg-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                View invoice
            </a>
        <?php endif; ?>

        <a href="<?= e(url('profile.php')) ?>"
           class="inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-surface-container-high text-on-surface font-body-bold text-caption-label uppercase tracking-wider hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">person</span>
            My bookings
        </a>

        <a href="<?= e(url('index.php')) ?>"
           class="inline-flex items-center gap-space-xs px-space-lg py-space-md rounded-xl bg-surface-container-high text-on-surface font-body-bold text-caption-label uppercase tracking-wider hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">home</span>
            Back to home
        </a>
    </div>
</section>
