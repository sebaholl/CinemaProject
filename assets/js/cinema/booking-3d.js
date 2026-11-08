// Entry point for the full-screen 3D booking page. Replaces the prototype's main.js.
// It loads real seat data from PHP, boots the (unchanged) CinemaScene, keeps the
// booking panel in sync, and hands the chosen seats to checkout.php.
//
// IMPORTANT: the seat rules here are only for friendly on-screen messages. The real
// checks (max seats, no single gap, seat still free) run again in PHP at checkout,
// because anything in the browser can be bypassed.

import { CinemaScene } from './CinemaScene.js';
import { buildSeatLayout } from './seat-layout.js';

const sceneEl = document.querySelector('#scene');
const elements = {
  scene: sceneEl,
  reset: document.querySelector('#reset-view'),
  toast: document.querySelector('#toast'),
  ticketCount: document.querySelector('#ticket-count'),
  ticketMinus: document.querySelector('#ticket-minus'),
  ticketPlus: document.querySelector('#ticket-plus'),
  seatsEmpty: document.querySelector('#seats-empty'),
  seatsList: document.querySelector('#seats-list'),
  seatsHint: document.querySelector('#seats-hint'),
  totalValue: document.querySelector('#total-value'),
  checkout: document.querySelector('#checkout'),
  form: document.querySelector('#booking-form'),
  seatsField: document.querySelector('#seats-field'),
};

const MIN_TICKETS = 1;
let MAX_TICKETS = 5; // overwritten by the server's maxSeats

let ticketCount = 2;
let selectedSeats = [];
let allSeats = [];
let cinema;
let toastTimer;

function showToast(message) {
  if (!elements.toast) return;
  window.clearTimeout(toastTimer);
  elements.toast.textContent = message;
  elements.toast.hidden = false;
  requestAnimationFrame(() => elements.toast.classList.add('toast--visible'));
  toastTimer = window.setTimeout(() => {
    elements.toast.classList.remove('toast--visible');
    window.setTimeout(() => { elements.toast.hidden = true; }, 240);
  }, 2800);
}

// --- Client mirror of the "no single gap" rule (friendly message only) ----------
// Returns true if booking `selectedIds` would strand a lone empty seat between two
// occupied seats in any row. Row ends are treated as open (a gap by the aisle is ok).
function wouldLeaveSingleGap(selectedIds) {
  const chosen = new Set(selectedIds);
  const rows = {};
  allSeats.forEach((s) => {
    (rows[s.row] = rows[s.row] || []).push(s);
  });

  return Object.values(rows).some((row) => {
    const ordered = row.slice().sort((a, b) => a.number - b.number);
    const occupied = ordered.map((s) => s.status === 'occupied' || chosen.has(s.seatId));
    for (let i = 1; i < ordered.length - 1; i += 1) {
      if (!occupied[i] && occupied[i - 1] && occupied[i + 1]) return true;
    }
    return false;
  });
}

function renderBooking() {
  const count = selectedSeats.length;

  if (elements.seatsEmpty) elements.seatsEmpty.hidden = count > 0;
  if (elements.seatsList) {
    elements.seatsList.hidden = count === 0;
    elements.seatsList.innerHTML = selectedSeats
      .map((seat) => `<li class="bk-seats__item"><span>${seat.id}</span><span>${seat.price} kr</span></li>`)
      .join('');
  }

  const total = selectedSeats.reduce((sum, seat) => sum + (seat.price || 0), 0);
  if (elements.totalValue) elements.totalValue.textContent = `${total} kr`;

  // Keep the hidden field in sync with the real DB seat ids, in selection order.
  if (elements.seatsField) {
    elements.seatsField.value = selectedSeats.map((s) => s.seatId).join(',');
  }

  const remaining = ticketCount - count;
  if (elements.seatsHint) {
    if (remaining > 0) {
      elements.seatsHint.hidden = false;
      elements.seatsHint.textContent = `Select ${remaining} more seat${remaining === 1 ? '' : 's'}.`;
    } else {
      elements.seatsHint.hidden = true;
    }
  }
  if (elements.checkout) elements.checkout.disabled = count === 0 || remaining > 0;
}

function setTicketCount(next) {
  ticketCount = Math.min(MAX_TICKETS, Math.max(MIN_TICKETS, next));
  if (elements.ticketCount) elements.ticketCount.textContent = ticketCount;
  if (cinema && selectedSeats.length > ticketCount) {
    cinema.trimTo(ticketCount);
  } else {
    renderBooking();
  }
}

function startScene(data) {
  allSeats = buildSeatLayout(data.seats);
  MAX_TICKETS = data.maxSeats || 5;
  ticketCount = Math.min(ticketCount, MAX_TICKETS);

  cinema = new CinemaScene(elements.scene, allSeats, {
    maxSeats: MAX_TICKETS,
    onSelectionChange: (seats) => {
      selectedSeats = seats;
      if (seats.length > ticketCount) {
        ticketCount = Math.min(MAX_TICKETS, seats.length);
        if (elements.ticketCount) elements.ticketCount.textContent = ticketCount;
      }
      renderBooking();
    },
    onUnavailable: (seat) => showToast(`Seat ${seat.id} is taken — pick another.`),
    onLimit: (max) => showToast(`You can pick up to ${max} seat${max === 1 ? '' : 's'}.`),
    onContextLost: () => showToast('The 3D view was paused by your device.'),
    onContextRestored: () => showToast('The 3D view is ready again.'),
  });

  renderBooking();
}

function showSceneError(message) {
  if (!elements.scene) return;
  elements.scene.innerHTML = `
    <div class="scene-error">
      <strong>The 3D view could not start</strong>
      <span>${message}</span>
    </div>`;
}

// --- Boot -----------------------------------------------------------------------
const seatsUrl = sceneEl ? sceneEl.dataset.seatsUrl : null;

if (seatsUrl) {
  fetch(seatsUrl, { headers: { Accept: 'application/json' } })
    .then((res) => res.json())
    .then((data) => {
      if (!data.ok) throw new Error(data.error || 'Could not load seats.');
      try {
        startScene(data);
      } catch (err) {
        console.error('Cinema 3D could not start:', err);
        showSceneError('Try the latest Chrome or Safari with hardware acceleration enabled.');
      }
    })
    .catch((err) => {
      console.error('Seat data failed to load:', err);
      showSceneError('Seat availability could not be loaded. Please refresh.');
    });
}

// Panel controls
elements.reset?.addEventListener('click', () => cinema?.resetView());
elements.ticketMinus?.addEventListener('click', () => setTicketCount(ticketCount - 1));
elements.ticketPlus?.addEventListener('click', () => setTicketCount(ticketCount + 1));

// Continue to checkout: final client-side sanity check, then POST to PHP.
elements.form?.addEventListener('submit', (event) => {
  if (selectedSeats.length === 0) {
    event.preventDefault();
    showToast('Please pick at least one seat.');
    return;
  }
  if (wouldLeaveSingleGap(selectedSeats.map((s) => s.seatId))) {
    event.preventDefault();
    showToast('Please avoid leaving a single empty seat between booked seats.');
  }
  // Otherwise the form submits normally to checkout.php (server re-checks everything).
});
