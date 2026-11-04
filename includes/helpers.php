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
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

// 105 -> "1h 45m"
function format_duration(int $minutes): string
{
    return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
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
