<?php
declare(strict_types=1);

// Server-side seat-selection rules for one screening. Pure logic, no database:
// it is handed the full seat list (already read from the DB) plus the seat ids the
// customer is trying to book, and returns a list of human-readable problems.
// JavaScript enforces the same rules for nice messages, but THIS is the real check
// because the browser can be bypassed.
final class SeatRules
{
    public const MAX_SEATS = 5;

    /**
     * Check a selection against the rules.
     *
     * @param Seat[] $allSeats        Every seat in the screening's hall, with its sold flag.
     * @param int[]  $selectedSeatIds The seat ids the customer wants to book.
     * @param int    $alreadyBookedByCustomer Seats this customer already holds (confirmed) for
     *                                this screening. The max-5 rule counts these PLUS the new
     *                                selection. The controller (checkout.php) passes the real
     *                                count once a customer is logged in, and 0 for guests.
     * @return string[] One message per broken rule. Empty array means the selection is allowed.
     */
    public static function validate(array $allSeats, array $selectedSeatIds, int $alreadyBookedByCustomer = 0): array
    {
        $errors   = [];
        $selected = array_values(array_unique(array_map('intval', $selectedSeatIds)));

        // Rule 0: must pick at least one seat.
        if (count($selected) === 0) {
            return ['Please select at least one seat.'];
        }

        // Rule 1: at most 5 seats per customer per screening — the customer's seats already
        // booked for this screening PLUS the new selection. $alreadyBookedByCustomer is threaded
        // in from the controller; it is the real count when a customer is logged in, and 0 for
        // guests (until login exists everyone shares one guest customer, so aggregating guest
        // bookings would wrongly block everyone — see checkout.php and current_customer_id()).
        if ($alreadyBookedByCustomer + count($selected) > self::MAX_SEATS) {
            if ($alreadyBookedByCustomer > 0) {
                $canAdd = max(0, self::MAX_SEATS - $alreadyBookedByCustomer);
                $errors[] = 'You can book at most ' . self::MAX_SEATS . ' seats per showing. You already have '
                    . $alreadyBookedByCustomer . ', so you can add at most ' . $canAdd . ' more.';
            } else {
                $errors[] = 'You can book at most ' . self::MAX_SEATS . ' seats in one booking.';
            }
        }

        // Index the seats by id so we can validate the posted ids.
        $byId = [];
        foreach ($allSeats as $seat) {
            $byId[$seat->getId()] = $seat;
        }

        // Rule 2: every posted seat must exist in this hall and still be free.
        foreach ($selected as $seatId) {
            if (!isset($byId[$seatId])) {
                $errors[] = 'One of the chosen seats does not belong to this screening.';
                continue;
            }
            if ($byId[$seatId]->isSold()) {
                $errors[] = 'Seat ' . self::label($byId[$seatId]) . ' is already taken.';
            }
        }

        // If the basics already failed, stop before the gap check (it assumes valid seats).
        if ($errors !== []) {
            return $errors;
        }

        // Rule 3: no awkward single gap.
        // Interpretation (documented for the exam): after this booking is applied, a row
        // must not contain a single empty seat with an occupied seat immediately on BOTH
        // sides ("occupied · empty · occupied"). Gaps of one seat at a row end (next to the
        // aisle/wall) are allowed, because that is not a gap "between reservations".
        $selectedSet = array_fill_keys($selected, true);

        // Group seats into rows, ordered by seat number.
        $rows = [];
        foreach ($allSeats as $seat) {
            $rows[$seat->getRowLabel()][$seat->getSeatNumber()] = $seat;
        }

        foreach ($rows as $rowLabel => $seatsByNumber) {
            ksort($seatsByNumber);
            $ordered = array_values($seatsByNumber);

            // Occupancy AFTER the booking: already sold OR chosen now.
            $occupied = [];
            foreach ($ordered as $seat) {
                $occupied[] = $seat->isSold() || isset($selectedSet[$seat->getId()]);
            }

            $last = count($ordered) - 1;
            for ($i = 1; $i < $last; $i++) {
                // An empty seat trapped between two occupied neighbours is a stranded single gap.
                if (!$occupied[$i] && $occupied[$i - 1] && $occupied[$i + 1]) {
                    $errors[] = 'Your selection would leave a single empty seat (row '
                        . $rowLabel . ', seat ' . $ordered[$i]->getSeatNumber()
                        . '). Please avoid leaving a lone gap between booked seats.';
                    break; // one message per row is enough
                }
            }
        }

        return $errors;
    }

    private static function label(Seat $seat): string
    {
        return $seat->getRowLabel() . $seat->getSeatNumber();
    }
}
