<?php
/**
 * Plain-text version of the ticket email. Presentation only.
 * Expects: $ticket (Ticket).
 *
 * Sent alongside the HTML version in the same message. The client shows whichever
 * it can handle, so a text-only reader still gets every fact - including the
 * ticket code, which is the part that actually gets scanned.
 *
 * No e() here: this is never parsed as HTML, so escaping would only show the
 * customer "&amp;" where their name has an "&".
 */
$line = str_repeat('-', 46);
?>
THANK YOU FOR YOUR PURCHASE

<?= strtoupper($ticket->getMovieTitle()) ?>


Show this ticket code at the cinema entrance:

    <?= $ticket->getTicketCode() ?>


<?= $line ?>

YOUR TICKET
<?= $line ?>

Valid for : <?= $ticket->getSeatCount() ?> <?= $ticket->getSeatCount() === 1 ? 'ticket' : 'tickets' ?>

Cinema    : <?= SITE_NAME ?>

Hall      : <?= $ticket->getHallName() ?>

Row       : <?= $ticket->getRowList() ?>

Seats     : <?= $ticket->getSeatList() ?>

Date      : <?= $ticket->getDateLabel() ?>

Time      : <?= $ticket->getScreeningTime() ?>

Format    : <?= $ticket->getFormat() ?>


<?= $line ?>

RECEIPT
<?= $line ?>

Ticket ID : <?= $ticket->getTicketCode() ?>

Booking   : #<?= $ticket->getBookingId() ?>

<?php if ($ticket->getInvoiceId() !== null): ?>
Invoice   : #<?= $ticket->getInvoiceId() ?>

<?php endif; ?>
Purchased : <?= $ticket->getBookedAtLabel() ?>


<?php foreach ($ticket->getSeats() as $seat): ?>
Seat <?= str_pad($seat['label'], 5) ?> (<?= $seat['type'] ?>)<?= str_repeat(' ', max(1, 16 - strlen($seat['type']))) ?><?= $seat['price'] ?> kr
<?php endforeach; ?>

TOTAL (incl. VAT): <?= $ticket->getTotal() ?> kr

<?php if ($ticket->getInvoiceId() !== null): ?>

View your invoice online:
<?= url_abs('invoice.php?booking=' . $ticket->getBookingId()) ?>

<?php endif; ?>

<?= $line ?>

Booked by <?= $ticket->getCustomerName() ?>.
Your tickets are also on your profile page at <?= SITE_NAME ?>.

This is a student project. The checkout is a mock - no real payment was taken.
