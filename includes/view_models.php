<?php
// View-model builders: turn domain objects (Movie, Showing, News, Seat) into the
// simple arrays the templates expect. This keeps the data layer (src/ classes) and
// the presentation layer (templates/) apart — the page controllers call these.

// One showtime "slot" (hero chips + weekly matrix). Matches templates/partials/showtime_slot.php.
function vm_showing_slot(Showing $s): array
{
    return [
        'id'         => $s->getId(),
        'screen'     => $s->getHallId(),              // "Screen N" = hall id
        'time'       => substr($s->getTime(), 0, 5),  // HH:MM
        'format'     => $s->getFormat(),
        'seats_left' => $s->getSeatsLeft(),
        'capacity'   => max(1, $s->getTotalSeats()),  // avoid divide-by-zero in seat_status()
    ];
}

// One poster card. Matches templates/partials/movie_card.php.
function vm_movie_card(Movie $m): array
{
    return [
        'id'       => $m->getId(),
        'title'    => $m->getTitle(),
        'genre'    => $m->getGenre(),
        'duration' => $m->getDurationMinutes(),
        'poster'   => $m->getCoverImage() ?? 'https://placehold.co/400x600/101014/e94560/png?text=No+Poster',
    ];
}

// One news/event card. Matches templates/partials/event_card.php.
function vm_news_card(News $n, bool $highlight = false): array
{
    $desc = $n->getDescription();
    return [
        'id'         => $n->getId(),
        'highlight'  => $highlight,
        'date_label' => format_date_label($n->getPublicationDate()),
        'category'   => $n->getCategory(),
        'title'      => $n->getTitle(),
        'excerpt'    => mb_strlen($desc) > 160 ? mb_substr($desc, 0, 157) . '…' : $desc,
        'image'      => $n->getImage()
            ?? 'https://placehold.co/800x450/101014/e94560/png?text=' . rawurlencode($n->getTitle()),
        'link_text'  => 'Read more',
    ];
}

// Weekly matrix: group the week's showings by movie, then into one column per day.
// $days is the output of week_days(); $movieById maps movie_id => Movie.
// @param Showing[] $weekShowings
function vm_week_schedule(array $weekShowings, array $movieById, array $days): array
{
    $dateToColumn = [];
    foreach ($days as $i => $day) {
        $dateToColumn[$day['date']] = $i;
    }

    $byMovie = [];
    foreach ($weekShowings as $s) {
        $col = $dateToColumn[$s->getDate()] ?? null;
        if ($col === null) { continue; }
        $mid = $s->getMovieId();
        if (!isset($byMovie[$mid])) {
            $byMovie[$mid] = array_fill(0, count($days), []); // empty column per day
        }
        $byMovie[$mid][$col][] = vm_showing_slot($s);
    }

    $schedule = [];
    foreach ($byMovie as $mid => $dayColumns) {
        $movie = $movieById[$mid] ?? null;
        if ($movie === null) { continue; }
        $schedule[] = [
            'title'    => $movie->getTitle(),
            'poster'   => $movie->getCoverImage() ?? 'https://placehold.co/400x600/101014/e94560/png?text=No+Poster',
            'duration' => $movie->getDurationMinutes(),
            'days'     => $dayColumns,
        ];
    }
    return $schedule;
}

// Seat map rows for the booking page. Matches templates/sections/seatmap.php.
// @param Seat[] $seats
function vm_seat_rows(array $seats): array
{
    $rows = [];
    foreach ($seats as $seat) {
        $ri = $seat->getRowIndex();
        if (!isset($rows[$ri])) {
            $rows[$ri] = ['row' => $ri, 'seats' => []];
        }
        $rows[$ri]['seats'][] = [
            'id'    => $seat->getId(),                      // real DB seat_id (posted back to PHP)
            'row'   => $ri,
            'num'   => $seat->getSeatNumber(),
            'type'  => $seat->getSeatType(),
            'state' => $seat->isSold() ? 'sold' : 'available',
            'price' => Pricing::priceFor($seat->getSeatType()),
        ];
    }
    ksort($rows);
    return array_values($rows);
}
