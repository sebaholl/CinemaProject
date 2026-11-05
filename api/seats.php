<?php
declare(strict_types=1);

// JSON endpoint: the live seat map for one screening, read straight from the DB.
// The 3D seat picker (assets/js/cinema/booking-3d.js) fetches this to know which
// seats exist and which are already sold. The 2D view uses the same data via PHP.
//
// GET api/seats.php?showing_id=123

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store'); // availability changes constantly

// Small helper so every exit path returns valid JSON.
function seats_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$showingId = isset($_GET['showing_id']) ? (int) $_GET['showing_id'] : 0;
if ($showingId < 1) {
    seats_json(['ok' => false, 'error' => 'Missing or invalid showing_id.'], 400);
}

try {
    $pdo       = Database::getInstance()->getConnection();
    $screening = (new ShowingRepository($pdo))->findById($showingId);

    if ($screening === null) {
        seats_json(['ok' => false, 'error' => 'Showing not found.'], 404);
    }

    $seats = (new SeatRepository($pdo))->findForScreening($showingId);

    // Shape each seat for the 3D scene. The scene uses the label `id` ("A1") for
    // display; `seatId` is the real DB id that gets posted back at checkout.
    $out = [];
    foreach ($seats as $seat) {
        $typeRaw = $seat->getSeatType(); // standard | luxury | accessible
        $out[] = [
            'id'      => $seat->getRowLabel() . $seat->getSeatNumber(),
            'seatId'  => $seat->getId(),
            'row'     => $seat->getRowLabel(),
            'number'  => $seat->getSeatNumber(),
            'typeRaw' => $typeRaw,
            // Display type for the 3D panel: luxury shows as "Premium", the rest "Standard".
            'type'    => $typeRaw === 'luxury' ? 'Premium' : 'Standard',
            'price'   => Pricing::priceFor($typeRaw),
            'status'  => $seat->isSold() ? 'occupied' : 'available',
        ];
    }

    seats_json([
        'ok'        => true,
        'maxSeats'  => SeatRules::MAX_SEATS,
        'screening' => [
            'id'     => $screening->getId(),
            'title'  => $screening->getMovieTitle(),
            'date'   => $screening->getDate(),
            'time'   => substr($screening->getTime(), 0, 5),
            'hallId' => $screening->getHallId(),
            'format' => $screening->getFormat(),
        ],
        'seats'     => $out,
    ]);
} catch (Throwable $e) {
    // Never leak internals to the client.
    seats_json(['ok' => false, 'error' => 'Could not load seats.'], 500);
}
