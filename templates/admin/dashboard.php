<h1 class="text-3xl font-bold mb-2">Dashboard</h1>
<p class="text-on-surface-variant mb-8">
    Cinema activity. Dates and times use UTC.
</p>

<div class="grid gap-4 sm:grid-cols-3 mb-10">
    <section class="rounded-xl bg-surface-container-low p-6">
        <h2 class="text-on-surface-variant">Today's bookings</h2>
        <p class="mt-3 text-3xl font-bold">
            <?= (int) $statistics['today_bookings'] ?>
        </p>
    </section>

    <section class="rounded-xl bg-surface-container-low p-6">
        <h2 class="text-on-surface-variant">Upcoming showings</h2>
        <p class="mt-3 text-3xl font-bold">
            <?= (int) $statistics['upcoming_showings'] ?>
        </p>
    </section>

    <section class="rounded-xl bg-surface-container-low p-6">
        <h2 class="text-on-surface-variant">Mock revenue this month</h2>
        <p class="mt-3 text-3xl font-bold">
            <?= e(number_format($statistics['month_revenue'], 2)) ?> kr
        </p>
    </section>
</div>

<h2 class="text-2xl font-bold mb-4">Newest bookings</h2>

<?php if ($recentBookings === []): ?>
    <p class="text-on-surface-variant">There are no bookings yet.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-xl bg-surface-container-low">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="p-4">Booking</th>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Movie</th>
                    <th class="p-4">Showing (UTC)</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBookings as $booking): ?>
                    <tr class="border-b border-white/10">
                        <td class="p-4">
                            #<?= (int) $booking['booking_id'] ?>
                        </td>
                        <td class="p-4">
                            <?= e($booking['customer_name']) ?>
                        </td>
                        <td class="p-4">
                            <?= e($booking['movie_title']) ?>
                        </td>
                        <td class="p-4">
                            <?= e($booking['screening_date']) ?>
                            <?= e(substr((string) $booking['screening_time'], 0, 5)) ?>
                        </td>
                        <td class="p-4">
                            <?= e($booking['status']) ?>
                        </td>
                        <td class="p-4">
                            <?= $booking['invoice_id'] === null
                                ? '—'
                                : '#' . (int) $booking['invoice_id'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>