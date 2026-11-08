# Seat reservation rules — test cases & forged-request proof

The seat rules are enforced **server-side** so a tampered browser cannot book an illegal
selection. This file lists every rule case with its expected result, and shows how to test
each one by **forging requests** (not just clicking the 3D UI).

- **Logic under test:** `SeatRules::validate($allSeats, $selectedSeatIds, $alreadyBookedByCustomer)`
  in [`src/SeatRules.php`](../src/SeatRules.php).
- **Where it runs:** [`checkout.php`](../checkout.php) calls it at `step=select` **and** again
  at `step=pay` (a seat can be taken between the two).
- **DB backstop:** `UNIQUE(screening_id, seat_id)` (`uq_screening_seat`) + the
  `trg_bookingseat_before_insert` trigger reject an illegal insert even if two users race past
  the PHP check at the same instant — see [`sql/cinema.sql`](../sql/cinema.sql).

## The rules
0. At least one seat must be selected.
1. **Max 5 seats per customer per showing** = the customer's already-booked seats for this
   showing **plus** the new selection. *(Live once login exists; for guests the count is 0, so
   today the effective cap is 5 per booking — see "Per-customer cap" below.)*
2. Every selected seat must **exist in this showing's hall** and must **not already be sold**.
3. **No single gap:** after booking, a row must not contain one empty seat with an occupied
   seat on **both** sides (`occupied · empty · occupied`). A lone empty seat at a row end
   (by the aisle/wall) is allowed. A gap of **two or more** empty seats is allowed.

---

## Test matrix

Two layers can reject a bad request, and the table names which one fires:

- **Controller** = `parse_seat_ids()` in `checkout.php` sanitises the raw POST first: it runs
  `intval`, drops anything `<= 0`, and de-duplicates **before** validation. So some malformed
  ids never reach `SeatRules` through the endpoint.
- **SeatRules** = the rule logic itself (what the CLI harness exercises directly).

| # | Scenario | Selected seat ids | Existing (same customer) | Expected result | Caught by |
|---|----------|-------------------|--------------------------|-----------------|-----------|
| 1 | Valid single seat | `[3]` | 0 | **Allowed** | — |
| 2 | Valid contiguous block of 3 | `[3,4,5]` | 0 | **Allowed** | — |
| 3 | Exactly 5 seats | `[1,2,3,4,5]` | 0 | **Allowed** | — |
| 4 | Six seats at once | `[1,2,3,4,5,6]` | 0 | **Rejected** — "at most 5 seats in one booking" | SeatRules (rule 1) |
| 5 | Single gap: A1 sold, book A3 | `[3]` (A1 sold) | 0 | **Rejected** — "single empty seat" | SeatRules (rule 3) |
| 6 | Gap of 2: A1 sold, book A4 | `[4]` (A1 sold) | 0 | **Allowed** (two empties is fine) | — |
| 7 | Lone gap at row end (A1 by wall) | `[2]` | 0 | **Allowed** | — |
| 8 | Seat already sold | `[5]` (A5 sold) | 0 | **Rejected** — "already taken" | SeatRules (rule 2) |
| 9 | Seat from another hall / nonexistent | `[999]` | 0 | **Rejected** — "does not belong" | SeatRules (rule 2) |
| 10 | Duplicate ids | `[5,5,5]` | 0 | **Allowed** — deduped, books seat 5 once | Controller dedupes (and SeatRules dedupes) |
| 11 | Negative id | `[-5]` | 0 | **Rejected** | Endpoint: Controller strips it → "select at least one seat". Direct: SeatRules → "does not belong" |
| 12 | Non-numeric id | `["abc"]` | 0 | **Rejected** | Endpoint: Controller `intval`→0, stripped → "select at least one seat". Direct: SeatRules → "does not belong" |
| 13 | Empty selection | `[]` | 0 | **Rejected** — "select at least one seat" | SeatRules (rule 0) |
| 14 | Per-customer: 3 held + 3 new | `[1,2,3]` | 3 | **Rejected** — "you already have 3…" | SeatRules (rule 1) — *dormant for guests* |
| 15 | Per-customer: 4 held + 1 new | `[1]` | 4 | **Allowed** (= 5) | — |

> Rows 11 and 12 behave differently depending on the layer, on purpose. Through the real
> endpoint the controller sanitises them away, so they surface as "select at least one seat".
> Called directly (as the CLI harness does, bypassing the controller) `SeatRules` rejects them
> as "does not belong". Both outcomes are rejections — the request never books a bad seat.

---

## Automated proof (unit level)

A no-framework PHP harness asserts every row above without a database:

```bash
# from the project root
/Applications/XAMPP/xamppfiles/bin/php tests/seat_rules_test.php
```

Expected: `15 passed, 0 failed` (exit code 0). Source: [`tests/seat_rules_test.php`](../tests/seat_rules_test.php).
This is the quickest way to show the rules hold, and it doubles as a regression guard.

---

## Forged-request proof (endpoint level)

This is the "never trust the browser" demo: we bypass the JavaScript entirely and POST a bad
selection straight to `checkout.php`. The server still rejects it.

Both methods below satisfy CSRF: `checkout.php` requires a valid `csrf_token` that matches the
session, so a forged request has to carry the **same session cookie and token** the booking
page issued.

### Method A — DevTools (most convincing "hacked browser")
1. Open a booking page, e.g. `http://localhost/CinemaProject/booking.php?showing_id=1&view=3d`.
2. Open DevTools → Elements, find the hidden field `<input id="seats-field" name="seats">`.
3. Edit its `value` to a forged list, e.g. `1,2,3,4,5,6` (six seats) or `999` (foreign seat).
4. Press **Continue to checkout**. The form submits with the real session + CSRF token, and the
   server responds with the rejection message — proving the JS limit was irrelevant.

### Method B — curl (scriptable)
```bash
BASE=http://localhost/CinemaProject

# 1) GET the booking page; save the session cookie.
curl -s -c cookies.txt "$BASE/booking.php?showing_id=1&view=3d" -o page.html

# 2) Pull the CSRF token out of the returned HTML.
TOKEN=$(grep -o 'name="csrf_token" value="[^"]*"' page.html | head -1 | sed 's/.*value="//;s/"//')
echo "token=$TOKEN"

# 3) Forge the "seats chosen" POST with six seats (rule 1) using that session + token.
curl -s -b cookies.txt \
  -d "step=select" \
  -d "showing_id=1" \
  -d "seats=1,2,3,4,5,6" \
  -d "csrf_token=$TOKEN" \
  "$BASE/checkout.php" | grep -i "at most 5"
```
Swap `seats=1,2,3,4,5,6` for other cases (`seats=999`, a known-sold id, `seats=-5`, etc.).
Use **real seat ids** for your data — read them from `api/seats.php?showing_id=1` (the `seatId`
field). A valid selection returns the card form instead of an error.

> Tip: to demo the per-customer cap (rows 14–15) before login exists, temporarily set
> `$_SESSION['customer_id']` to a customer that already holds seats for the showing, then forge
> a request that pushes the total over 5. Remove the temporary line afterwards.

---

## Report notes
- **Web Programming half:** use the **Test matrix** as the rule table, and draw the booking
  **validation flowchart** (select → CSRF → sanitise → `SeatRules::validate` → session → pay →
  re-validate → `BookingRepository::create`). Screenshot a forged curl/DevTools rejection as the
  "never trust the browser" evidence.
- **Database half:** explain how `uq_screening_seat` and `trg_bookingseat_before_insert` back up
  rules 1–2 at the DB level (defence in depth), catching the race that PHP alone cannot.
