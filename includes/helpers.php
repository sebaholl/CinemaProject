<?php
// Small helper functions used by the templates.

// Escape text before printing it in HTML (protects against XSS).
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Build a link to a page, e.g. url('movies.php')
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// Build a link to a file in assets/, e.g. asset('css/site.css')
// Appends the file's last-modified time (?v=...) so browsers always fetch the
// newest version after we edit a stylesheet or script, instead of a stale cache.
function asset(string $path): string
{
    $clean = ltrim($path, '/');
    $url   = url('assets/' . $clean);
    $file  = __DIR__ . '/../assets/' . $clean;
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

// 105 -> "1h 45m"
function format_duration(int $minutes): string
{
    return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
}

// The next $count days starting today (UTC, to match the DB dates).
// Used for the date pills and the weekly showtime matrix columns.
function week_days(int $count = 7): array
{
    $count = max(1, $count);
    $days  = [];
    $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    for ($i = 0; $i < $count; $i++) {
        $d = $today->modify("+$i day");
        $days[] = [
            'date'  => $d->format('Y-m-d'),
            'label' => $d->format('D, j M'),
            'pill'  => ($i === 0 ? 'Today • ' : '') . $d->format('D j'),
        ];
    }
    return $days;
}

// '2026-11-05' -> '5 November'. Falls back to the raw value if it cannot parse.
function format_date_label(string $ymd): string
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd, new DateTimeZone('UTC'));
    return $d ? $d->format('j F') : $ymd;
}

// Date label for the booking page, with "(Today)" appended when relevant.
function format_showing_date(string $ymd): string
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd, new DateTimeZone('UTC'));
    if (!$d) { return $ymd; }
    $label = $d->format('D, j M');
    if ($ymd === gmdate('Y-m-d')) { $label .= ' (Today)'; }
    return $label;
}

// --- CSRF protection -------------------------------------------------------
// One token per session. Forms embed it with csrf_field(); the receiving script
// calls csrf_check() before trusting a POST. Stops other sites from submitting
// our forms on a logged-in user's behalf.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden input to drop inside a <form>. Already escaped.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// True only if the POSTed token matches the session token (constant-time compare).
function csrf_check(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

// --- Current customer ------------------------------------------------------
// The logged-in customer's id, or null for a guest/admin. Auth stores the
// signed-in account under $_SESSION['auth'] (role + id); for a customer that
// id IS the customer_id. We only return it when the role is 'customer', so an
// admin session counts as null here (admins don't book seats).
// This is the single switch for the "max 5 per customer" rule: while it is null
// the checkout counts 0 existing seats, and it becomes a true per-customer cap
// the moment a customer logs in — with no change to the validation code.
function current_customer_id(): ?int
{
    $auth = $_SESSION['auth'] ?? null;
    if (!is_array($auth) || ($auth['role'] ?? null) !== 'customer') {
        return null;
    }
    $id = $auth['id'] ?? null;
    return is_int($id) && $id > 0 ? $id : null;
}

// Decide how full a showing is. Used to pick the slot colour.
// Thresholds match the legend: 20-100% ok, 10-20% low, 1-10% critical, 0 sold out.
// Later this logic moves to the Showing class / the "seats remaining" view.
function seat_status(int $seatsLeft, int $capacity): string
{
    if ($seatsLeft <= 0) {
        return 'sold_out';
    }
    $percent = $seatsLeft / $capacity * 100;
    if ($percent <= 10) {
        return 'critical';
    }
    if ($percent < 20) {
        return 'low';
    }
    return 'ok';
}

// Markup for a single seat glyph used in the 2D seating plan.
// Accessible seats keep the wheelchair symbol; all other seats use the
// custom cinema-chair SVG. Colour is driven by the .seat CSS (fill: currentColor).
function seat_icon(string $type = 'standard'): string
{
    if ($type === 'accessible') {
        return '<span class="material-symbols-outlined">accessible</span>';
    }

    return '<svg class="seat-icon" viewBox="0 0 32 27" fill="currentColor" aria-hidden="true" focusable="false">'
        . '<path d="M2.7,19.2c-0.8,0-1.5-0.5-1.8-1.3c-0.7-2.2-1-4.8-1-7.8c0-3,0.2-5.2,0.5-6.8c0.2-1,1.2-1.6,2.2-1.4'
        . 'c1,0.2,1.6,1.2,1.4,2.2c-0.3,1.3-0.4,3.3-0.4,6c0,2.7,0.3,4.9,0.8,6.8c0.3,1-0.3,2-1.2,2.3C3.1,19.2,2.9,19.2,2.7,19.2z'
        . ' M31,17.9c0.7-2.2,1-4.8,1-7.8c0-3-0.2-5.2-0.5-6.8c-0.2-1-1.2-1.6-2.2-1.4c-1,0.2-1.6,1.2-1.4,2.2'
        . 'c0.3,1.3,0.4,3.3,0.4,6c0,2.7-0.3,4.9-0.8,6.8c-0.3,1,0.3,2,1.2,2.3c0.2,0.1,0.4,0.1,0.5,0.1C30,19.2,30.8,18.7,31,17.9z'
        . ' M6.4,3.5c-0.3,1.7-0.5,3.9-0.5,6.5c0,2.6,0.1,4.7,0.4,6.3c0.2,1.3,1.2,2.4,2.4,2.8c1.8,0.6,4.2,0.9,7.2,0.9'
        . 's5.4-0.3,7.2-0.9c1.3-0.4,2.2-1.5,2.4-2.8c0.3-1.7,0.4-3.8,0.4-6.3c0-2.7-0.2-4.8-0.5-6.5c-0.3-1.3-1.2-2.4-2.6-2.8'
        . 'c-1.6-0.5-4-0.7-7-0.7s-5.4,0.2-7,0.7C7.7,1.1,6.7,2.2,6.4,3.5z'
        . ' M26.9,24c0.9-0.5,1.3-1.5,0.8-2.5c-0.5-0.9-1.6-1.3-2.5-0.8c-2.2,1.1-5.3,1.6-9.2,1.6c-4,0-7.1-0.6-9.2-1.6'
        . 'c-0.9-0.4-2-0.1-2.5,0.8c-0.5,0.9-0.1,2,0.8,2.5c2.7,1.4,6.3,2,10.9,2C20.6,26,24.2,25.3,26.9,24z"/>'
        . '</svg>';
}
