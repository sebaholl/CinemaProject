// Turns the DB seat list (from api/seats.php) into the seat objects CinemaScene
// draws. CinemaScene is kept exactly as it was in the prototype, so it still wants
// { id, row, number, type, price, status, position:{x,y,z} }. We add `seatId`
// (the real database id) so the booking panel can post it back to PHP.
//
// Positions are computed here, not stored in the DB, so the backend stays simple.
// Our halls are a uniform 8 rows (A–H) of 10 seats, with a centre aisle.

const SPACING = 1.05;   // gap between seats in a row
const AISLE_GAP = 0.72; // extra gap at the centre aisle
const SEATS_PER_ROW = 10;

// 'A' -> 0, 'B' -> 1, ... matches the step/platform tiers CinemaScene builds.
function rowIndexFromLabel(label) {
  return label.toUpperCase().charCodeAt(0) - 65;
}

export function buildSeatLayout(apiSeats) {
  // Work out how many seats each row actually has (defensive if a hall differs).
  const countsByRow = {};
  apiSeats.forEach((s) => {
    countsByRow[s.row] = Math.max(countsByRow[s.row] || 0, s.number);
  });

  return apiSeats.map((seat) => {
    const rowIndex = rowIndexFromLabel(seat.row);
    const count = countsByRow[seat.row] || SEATS_PER_ROW;
    const seatIndex = seat.number - 1; // 0-based position in the row

    const rowWidth = (count - 1) * SPACING + AISLE_GAP;
    const isRightOfAisle = seatIndex >= count / 2;
    const x = seatIndex * SPACING - rowWidth / 2 + (isRightOfAisle ? AISLE_GAP : 0);

    return {
      id: seat.id,              // "A1" — label used by the scene for display/lookup
      seatId: seat.seatId,      // real DB seat_id — posted back at checkout
      row: seat.row,
      number: seat.number,
      type: seat.type,          // "Standard" | "Premium" (display)
      typeRaw: seat.typeRaw,    // "standard" | "luxury" | "accessible"
      price: seat.price,        // kr, from the server
      status: seat.status,      // "available" | "occupied"
      position: {
        x,
        y: -0.1 + rowIndex * 0.36,
        z: -4.7 + rowIndex * 1.6,
      },
    };
  });
}
