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
