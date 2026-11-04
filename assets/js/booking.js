// Booking page: pick seats, keep the summary in sync.
// NOTE: this is only the front-end helper. The real seat rules (max 5 seats,
// no awkward single gaps, seat still free) must be checked again in PHP when
// the form is submitted, because JavaScript can be bypassed.
(function () {
    var MAX_SEATS = 5;

    var countEl    = document.getElementById('ticket-count');
    var listEl     = document.getElementById('selected-list');
    var totalEl    = document.getElementById('selected-total');
    var hintEl     = document.getElementById('selected-hint');
    var fieldEl    = document.getElementById('seats-field');
    var continueBtn = document.getElementById('continue-btn');
    var decBtn = document.querySelector('[data-step="dec"]');
    var incBtn = document.querySelector('[data-step="inc"]');
    var seats  = document.querySelectorAll('[data-seat]');

    if (!countEl || !seats.length) return;

    var target   = parseInt(countEl.textContent, 10) || 1;  // how many seats to pick
    var selected = [];                                       // chosen seat elements, in order

    function typeLabel(type) {
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    function deselect(seat) {
        seat.classList.remove('seat--selected');
        seat.setAttribute('aria-pressed', 'false');
        selected = selected.filter(function (s) { return s !== seat; });
    }

    function select(seat) {
        seat.classList.add('seat--selected');
        seat.setAttribute('aria-pressed', 'true');
        selected.push(seat);
    }

    function render() {
        // Keep the number of chosen seats within the ticket count.
        while (selected.length > target) {
            deselect(selected[selected.length - 1]);
        }

        // Summary list + total.
        var total = 0;
        if (selected.length === 0) {
            listEl.innerHTML = '<li class="font-caption-label text-caption-label text-on-surface-variant italic">No seats selected yet.</li>';
        } else {
            listEl.innerHTML = '';
            selected.slice().sort(function (a, b) {
                return (a.dataset.row - b.dataset.row) || (a.dataset.num - b.dataset.num);
            }).forEach(function (seat) {
                var price = parseInt(seat.dataset.price, 10) || 0;
                total += price;
                var li = document.createElement('li');
                li.className = 'flex items-center justify-between font-caption-label text-caption-label text-on-surface';
                li.innerHTML =
                    '<span>Row ' + seat.dataset.row + ' · Seat ' + seat.dataset.num +
                    ' <span class="text-on-surface-variant">(' + typeLabel(seat.dataset.type) + ')</span></span>' +
                    '<span>' + price + ' kr</span>';
                listEl.appendChild(li);
            });
        }
        totalEl.textContent = total + ' kr';

        // Hint + continue button.
        var remaining = target - selected.length;
        if (remaining > 0) {
            hintEl.textContent = 'Select ' + remaining + ' more seat' + (remaining === 1 ? '' : 's') + '.';
            hintEl.className = 'font-caption-label text-caption-label text-secondary';
        } else {
            hintEl.textContent = 'Ready — continue to checkout.';
            hintEl.className = 'font-caption-label text-caption-label text-tertiary';
        }
        continueBtn.disabled = selected.length === 0 || selected.length !== target;

        // Store chosen seat ids for the PHP side.
        fieldEl.value = selected.map(function (s) { return s.dataset.seatId; }).join(',');

        // Steppers reflect the limits.
        countEl.textContent = target;
        decBtn.disabled = target <= 1;
        incBtn.disabled = target >= MAX_SEATS;
    }

    seats.forEach(function (seat) {
        seat.addEventListener('click', function () {
            if (seat.classList.contains('seat--selected')) {
                deselect(seat);
            } else if (selected.length < target && selected.length < MAX_SEATS) {
                select(seat);
            } else {
                // At the limit: nudge the hint so the user knows why nothing happened.
                hintEl.textContent = 'You have selected ' + target + ' seat' + (target === 1 ? '' : 's') +
                    '. Remove one or add a ticket first.';
                hintEl.className = 'font-caption-label text-caption-label text-primary';
                return;
            }
            render();
        });
    });

    decBtn.addEventListener('click', function () {
        if (target > 1) { target--; render(); }
    });
    incBtn.addEventListener('click', function () {
        if (target < MAX_SEATS) { target++; render(); }
    });

    render();
})();
