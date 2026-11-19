<?php
/**
 * HTML ticket confirmation email. Presentation only.
 * Expects: $ticket (Ticket), provided by TicketMailer::render().
 *
 * Why this looks nothing like the rest of templates/: email clients are not
 * browsers. They strip <script> (so no Tailwind CDN), many ignore <style>
 * blocks, and almost none support flexbox or grid. So this is built the way
 * emails have always been built: nested tables, width attributes, and every
 * single style written inline. Keep it under 600px wide.
 *
 * Colours are the site's own palette from assets/js/tailwind-config.js.
 */

$poster   = $ticket->getPosterUrl();
$posterSrc = ($poster !== null && $poster !== '') ? url_abs($poster) : null;
$qrSrc    = QrCode::imageUrl($ticket->getTicketCode(), 200);
$invoiceUrl = url_abs('invoice.php?booking=' . $ticket->getBookingId());

// Repeated inline styles, named once so the markup below stays readable.
$muted    = 'color:#9a9aa6;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;';
$label    = 'color:#680019;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;';
$value    = 'color:#3d0010;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;line-height:18px;text-align:right;';
$rowLabel = 'color:#9a9aa6;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;padding:6px 0;';
$rowValue = 'color:#e2e2e8;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;padding:6px 0;text-align:right;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($ticket->getMovieTitle()) ?></title>
</head>
<body style="margin:0;padding:0;background-color:#111318;">

<!-- Preview line: what the inbox shows next to the subject, hidden in the email itself. -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    <?= e($ticket->getSeatCount()) ?> ticket<?= $ticket->getSeatCount() === 1 ? '' : 's' ?> ·
    <?= e($ticket->getDateLabel()) ?> at <?= e($ticket->getScreeningTime()) ?> ·
    <?= e($ticket->getTicketCode()) ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#111318;">
<tr>
<td align="center" style="padding:28px 16px;">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;">

        <!-- Heading -->
        <tr>
            <td align="center" style="padding:0 8px 24px 8px;">
                <p style="margin:0 0 8px 0;color:#9a9aa6;font-family:Arial,Helvetica,sans-serif;font-size:13px;letter-spacing:1px;text-transform:uppercase;">
                    Thank you for your purchase
                </p>
                <h1 style="margin:0 0 10px 0;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:30px;line-height:36px;font-weight:bold;">
                    <?= e($ticket->getMovieTitle()) ?>
                </h1>
                <p style="margin:0;<?= $muted ?>">
                    Show the QR code below when you arrive at the cinema.
                </p>
            </td>
        </tr>

        <!-- Poster + ticket -->
        <tr>
            <td style="padding:0 0 20px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <?php if ($posterSrc !== null): ?>
                    <td width="190" valign="top" style="width:190px;padding:0 10px 0 0;">
                        <img src="<?= e($posterSrc) ?>" width="190" alt="<?= e($ticket->getMovieTitle()) ?> poster"
                             style="display:block;width:190px;max-width:100%;height:auto;border:0;border-radius:12px;">
                    </td>
                    <?php endif; ?>

                    <td valign="top" style="padding:0;">
                        <!-- The ticket card -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="background-color:#ff5167;border-radius:12px;">
                        <tr>
                            <td align="center" style="padding:18px 18px 8px 18px;">
                                <!-- QR on a white card: QR readers need a light quiet zone around the code. -->
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                       style="background-color:#ffffff;border-radius:10px;">
                                <tr>
                                    <td style="padding:10px;">
                                        <img src="<?= e($qrSrc) ?>" width="180" height="180"
                                             alt="QR code for ticket <?= e($ticket->getTicketCode()) ?>"
                                             style="display:block;width:180px;height:180px;border:0;">
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" style="padding:0 18px 12px 18px;">
                                <!-- The code in plain text as well. Most mail clients block remote
                                     images by default, and the QR above IS a remote image, so the
                                     ticket has to still work when it does not load. -->
                                <p style="margin:0;color:#3d0010;font-family:'Courier New',Courier,monospace;font-size:17px;font-weight:bold;letter-spacing:2px;">
                                    <?= e($ticket->getTicketCode()) ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:0 18px 18px 18px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                       style="border-top:1px solid rgba(61,0,16,0.25);">
                                <tr>
                                    <td style="<?= $label ?>padding:10px 0 0 0;">TICKET</td>
                                    <td style="<?= $value ?>padding:10px 0 0 0;">
                                        Valid for <?= (int) $ticket->getSeatCount() ?> <?= $ticket->getSeatCount() === 1 ? 'ticket' : 'tickets' ?>
                                    </td>
                                </tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Cinema</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e(SITE_NAME) ?></td></tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Hall</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e($ticket->getHallName()) ?></td></tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Row</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e($ticket->getRowList()) ?></td></tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Seats</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e($ticket->getSeatList()) ?></td></tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Date</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e($ticket->getDateLabel()) ?></td></tr>
                                <tr><td style="<?= $label ?>padding:6px 0 0 0;">Time</td>
                                    <td style="<?= $value ?>padding:6px 0 0 0;"><?= e($ticket->getScreeningTime()) ?></td></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>

        <!-- Receipt -->
        <tr>
            <td style="padding:0 0 20px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background-color:#1a1c20;border-radius:12px;">
                <tr>
                    <td style="padding:20px;">
                        <p style="margin:0 0 12px 0;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;">
                            Receipt
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr><td style="<?= $rowLabel ?>">Ticket ID</td>
                            <td style="<?= $rowValue ?>"><?= e($ticket->getTicketCode()) ?></td></tr>
                        <tr><td style="<?= $rowLabel ?>">Booking</td>
                            <td style="<?= $rowValue ?>">#<?= (int) $ticket->getBookingId() ?></td></tr>
                        <?php if ($ticket->getInvoiceId() !== null): ?>
                        <tr><td style="<?= $rowLabel ?>">Invoice</td>
                            <td style="<?= $rowValue ?>">#<?= (int) $ticket->getInvoiceId() ?></td></tr>
                        <?php endif; ?>
                        <tr><td style="<?= $rowLabel ?>">Format</td>
                            <td style="<?= $rowValue ?>"><?= e($ticket->getFormat()) ?></td></tr>
                        <tr><td style="<?= $rowLabel ?>">Purchased</td>
                            <td style="<?= $rowValue ?>"><?= e($ticket->getBookedAtLabel()) ?></td></tr>
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="margin-top:12px;border-top:1px solid #333539;">
                        <?php foreach ($ticket->getSeats() as $seat): ?>
                            <tr>
                                <td style="<?= $rowLabel ?>">
                                    Seat <?= e($seat['label']) ?> <span style="color:#6f6f7a;">(<?= e($seat['type']) ?>)</span>
                                </td>
                                <td style="<?= $rowValue ?>"><?= (int) $seat['price'] ?> kr</td>
                            </tr>
                        <?php endforeach; ?>
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="margin-top:4px;border-top:1px solid #333539;">
                        <tr>
                            <td style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;padding:12px 0 0 0;">
                                Total (incl. VAT)
                            </td>
                            <td style="color:#ff5167;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:bold;padding:12px 0 0 0;text-align:right;">
                                <?= (int) $ticket->getTotal() ?> kr
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>

        <!-- Button -->
        <?php if ($ticket->getInvoiceId() !== null): ?>
        <tr>
            <td align="center" style="padding:0 0 20px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="background-color:#ff5167;border-radius:10px;">
                        <a href="<?= e($invoiceUrl) ?>"
                           style="display:inline-block;padding:12px 24px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;letter-spacing:1px;text-decoration:none;text-transform:uppercase;">
                            View invoice online
                        </a>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Footer -->
        <tr>
            <td align="center" style="padding:16px 8px 0 8px;border-top:1px solid #282a2e;">
                <p style="margin:0 0 6px 0;<?= $muted ?>">
                    Booked by <?= e($ticket->getCustomerName()) ?>. Your tickets are also on your
                    profile page at <?= e(SITE_NAME) ?>.
                </p>
                <p style="margin:0;color:#6f6f7a;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;">
                    This is a student project. The checkout is a mock - no real payment was taken.
                </p>
            </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
