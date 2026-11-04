// Date pills on the showtimes section: click a date to highlight that day's column.
(function () {
    var pills = document.querySelectorAll('[data-day-pill]');
    var columns = document.querySelectorAll('[data-day-col]');
    if (!pills.length) return;

    var activeClasses = ['bg-primary-container', 'text-on-primary', 'shadow-[0_0_16px_rgba(255,81,103,0.35)]'];
    var idleClasses = ['text-on-surface-variant'];

    function selectDay(day) {
        pills.forEach(function (pill) {
            var isActive = pill.dataset.dayPill === day;
            activeClasses.forEach(function (c) { pill.classList.toggle(c, isActive); });
            idleClasses.forEach(function (c) { pill.classList.toggle(c, !isActive); });
        });
        columns.forEach(function (col) {
            col.classList.toggle('opacity-40', col.dataset.dayCol !== day);
        });
    }

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () { selectDay(pill.dataset.dayPill); });
    });
})();
