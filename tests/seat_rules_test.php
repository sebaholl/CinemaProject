<?php
declare(strict_types=1);

// Vanilla-PHP test harness for the server-side seat rules (no framework, no DB).
// It builds Seat objects in memory and asserts SeatRules::validate() for every case
// in docs/rules-tests.md. This proves "all test cases give the right result" without
// needing XAMPP or the database.
//
// Run from the project root:   php tests/seat_rules_test.php
// Exit code is 0 when every case passes, 1 if any case fails (handy for CI later).

require_once __DIR__ . '/../src/Seat.php';
require_once __DIR__ . '/../src/SeatRules.php';

// --- Test hall -------------------------------------------------------------
// Two rows (A, B) of 10 seats, all standard. Seat ids run 1..20:
//   A1..A10 = ids 1..10,  B1..B10 = ids 11..20.
// $soldIds marks seats as already sold (booked by someone) for this screening.
/** @return Seat[] */
function make_hall(array $soldIds = []): array
{
    $sold  = array_fill_keys(array_map('intval', $soldIds), true);
    $seats = [];
    $id    = 1;
    foreach (['A', 'B'] as $row) {
        for ($n = 1; $n <= 10; $n++) {
            $seats[] = new Seat($id, $row, $n, 'standard', isset($sold[$id]));
            $id++;
        }
    }
    return $seats;
}

// --- Tiny assertion runner -------------------------------------------------
$passed = 0;
$failed = 0;

/**
 * Run one case.
 * @param string   $name     Human description.
 * @param Seat[]   $seats    The hall.
 * @param array    $selected Seat ids the "customer" is trying to book (may be forged).
 * @param int      $existing Seats the customer already holds for this screening.
 * @param string   $expect   '' means the selection must be ALLOWED (no errors);
 *                           otherwise a substring that must appear in the error output.
 */
function check(string $name, array $seats, array $selected, int $existing, string $expect): void
{
    global $passed, $failed;

    $errors = SeatRules::validate($seats, $selected, $existing);
    $joined = implode(' ', $errors);

    if ($expect === '') {
        $ok = ($errors === []);
    } else {
        $ok = (stripos($joined, $expect) !== false);
    }

    if ($ok) {
        $passed++;
        printf("  PASS  %s\n", $name);
    } else {
        $failed++;
        printf("  FAIL  %s\n", $name);
        printf("        expected: %s\n", $expect === '' ? '(allowed, no errors)' : "contains \"$expect\"");
        printf("        got:      %s\n", $joined === '' ? '(allowed, no errors)' : $joined);
    }
}

echo "Seat rule tests\n---------------\n";

// 1. Valid single seat.
check('valid single seat', make_hall(), [3], 0, '');

// 2. Valid contiguous block of 3.
check('valid block of 3', make_hall(), [3, 4, 5], 0, '');

// 3. Valid exactly 5 seats (the max in one booking).
check('valid exactly 5 seats', make_hall(), [1, 2, 3, 4, 5], 0, '');

// 4. Six seats in one go -> over the max.
check('6 seats rejected', make_hall(), [1, 2, 3, 4, 5, 6], 0, 'at most 5 seats in one booking');

// 5. Gap of 1: A1 sold, book A3 -> A2 stranded between two occupied seats.
check('single gap (1) rejected', make_hall([1]), [3], 0, 'single empty seat');

// 6. Gap of 2: A1 sold, book A4 -> A2 and A3 empty together, which is allowed.
check('gap of 2 allowed', make_hall([1]), [4], 0, '');

// 7. Single empty seat at the row end (next to the aisle/wall) is allowed.
check('row-end gap allowed', make_hall(), [2], 0, '');

// 8. A seat that is already sold cannot be booked again.
check('already-taken seat rejected', make_hall([5]), [5], 0, 'already taken');

// 9. A seat id that does not exist in this hall (e.g. from another venue).
check('foreign/nonexistent seat rejected', make_hall(), [999], 0, 'does not belong');

// 10. Duplicate ids are de-duplicated, not rejected: [5,5,5] books seat 5 once.
check('duplicate ids deduped (allowed)', make_hall(), [5, 5, 5], 0, '');

// 11. Negative id -> not a real seat -> rejected as "does not belong".
check('negative id rejected', make_hall(), [-5], 0, 'does not belong');

// 12. Non-numeric id -> intval() makes it 0 -> not a real seat -> rejected.
check('string id rejected', make_hall(), ['abc'], 0, 'does not belong');

// 13. Empty selection.
check('empty selection rejected', make_hall(), [], 0, 'at least one seat');

// 14. Per-customer cap: already holds 3, tries 3 more -> 6 > 5 -> rejected.
//     (Dormant for guests; live once login sets the customer id — see checkout.php.)
check('per-customer 3 existing + 3 new rejected', make_hall(), [1, 2, 3], 3, 'already have 3');

// 15. Per-customer cap: already holds 4, tries 1 more -> exactly 5 -> allowed.
check('per-customer 4 existing + 1 new allowed', make_hall(), [1], 4, '');

// --- Summary ---------------------------------------------------------------
echo "---------------\n";
printf("%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
