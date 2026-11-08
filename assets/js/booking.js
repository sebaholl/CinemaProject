// Booking page: pick a ticket count, then click one seat to grab that many
// adjacent seats in the same row at once (with a hover preview of what you'll get).
// NOTE: this is only the front-end helper. The real seat rules (max 5 seats,
// no awkward single gaps, seat still free) must be checked again in PHP when
// the form is submitted, because JavaScript can be bypassed.
(function () {
    var MAX_SEATS = 5;

    var countEl     = document.getElementById('ticket-count');
    var listEl      = document.getElementById('selected-list');
    var totalEl     = document.getElementById('selected-total');
    var hintEl      = document.getElementById('selected-hint');
    var fieldEl     = document.getElementById('seats-field');
    var continueBtn = document.getElementById('continue-btn');
    var decBtn = document.querySelector('[data-step="dec"]');
    var incBtn = document.querySelector('[data-step="inc"]');
    var seats  = document.querySelectorAll('[data-seat]');

    if (!countEl || !seats.length) return;

    var target   = parseInt(countEl.textContent, 10) || 1;  // how many seats to pick
    var selected = [];                                       // chosen seat elements, in order
    var anchor   = null;                                     // the seat the block starts from

    // Index the available seats by row + number so we can find neighbours quickly.
    // Only available seats are buttons, so a missing number here means "sold or no seat".
    var byRow = {};
    seats.forEach(function (seat) {
        var row = seat.dataset.row;
        (byRow[row] = byRow[row] || {})[parseInt(seat.dataset.num, 10)] = seat;
    });
    function seatAt(row, num) {
        return (byRow[row] && byRow[row][num]) || null;
    }

    // Build a run of up to `n` seats that are side by side in the same row,
    // starting from the clicked seat and growing right first, then left.
    // A sold seat or the end of the row stops the run, so the block is always gap-free.
    function contiguousBlock(row, startNum, n) {
        var first = seatAt(row, startNum);
        if (!first) return [];
        var block = [first];
        var right = startNum + 1;
        while (block.length < n && seatAt(row, right)) { block.push(seatAt(row, right)); right++; }
        var left = startNum - 1;
        while (block.length < n && seatAt(row, left)) { block.unshift(seatAt(row, left)); left--; }
        return block;
    }

    function typeLabel(type) {
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    function clearSelection() {
        selected.forEach(function (seat) {
            seat.classList.remove('seat--selected');
            seat.setAttribute('aria-pressed', 'false');
        });
        selected = [];
    }

    function selectBlock(block) {
        clearSelection();
        block.forEach(function (seat) {
            seat.classList.add('seat--selected');
            seat.setAttribute('aria-pressed', 'true');
            selected.push(seat);
        });
        render();
    }

    function clearPreview() {
        document.querySelectorAll('.seat--preview').forEach(function (seat) {
            seat.classList.remove('seat--preview');
        });
    }

    // Show which seats a click would grab, so the choice is visible before committing.
    function showPreview(seat) {
        clearPreview();
        contiguousBlock(seat.dataset.row, parseInt(seat.dataset.num, 10), Math.min(target, MAX_SEATS))
            .forEach(function (s) { s.classList.add('seat--preview'); });
    }

    function render() {
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
        if (selected.length === 0) {
            hintEl.textContent = 'Click a seat to grab ' + target + ' seat' + (target === 1 ? '' : 's') + ' together.';
            hintEl.className = 'font-caption-label text-caption-label text-secondary';
        } else if (selected.length < target) {
            // The clicked spot did not have enough seats side by side for the whole group.
            hintEl.textContent = 'Only ' + selected.length + ' seat' + (selected.length === 1 ? '' : 's') +
                ' fit together there — pick another spot for ' + target + '.';
            hintEl.className = 'font-caption-label text-caption-label text-primary';
        } else {
            hintEl.textContent = 'Ready — continue to checkout.';
            hintEl.className = 'font-caption-label text-caption-label text-tertiary';
        }
        continueBtn.disabled = selected.length !== target;

        // Store chosen seat ids for the PHP side.
        fieldEl.value = selected.map(function (s) { return s.dataset.seatId; }).join(',');

        // Steppers reflect the limits.
        countEl.textContent = target;
        decBtn.disabled = target <= 1;
        incBtn.disabled = target >= MAX_SEATS;
    }

    // Re-pick the block from the same starting seat whenever the ticket count changes,
    // so bumping the count up or down grows/shrinks the group automatically.
    function setTarget(next) {
        target = Math.max(1, Math.min(MAX_SEATS, next));
        if (anchor) {
            selectBlock(contiguousBlock(anchor.dataset.row, parseInt(anchor.dataset.num, 10), target));
        } else {
            render();
        }
    }

    seats.forEach(function (seat) {
        seat.addEventListener('click', function () {
            clearPreview();
            if (seat.classList.contains('seat--selected')) {
                // Clicking inside the current group clears it.
                clearSelection();
                anchor = null;
                render();
                return;
            }
            anchor = seat;
            selectBlock(contiguousBlock(seat.dataset.row, parseInt(seat.dataset.num, 10), Math.min(target, MAX_SEATS)));
        });

        seat.addEventListener('mouseenter', function () { showPreview(seat); });
        seat.addEventListener('mouseleave', clearPreview);
    });

    decBtn.addEventListener('click', function () { setTarget(target - 1); });
    incBtn.addEventListener('click', function () { setTarget(target + 1); });

    render();
})();
