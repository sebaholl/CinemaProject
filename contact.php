<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance()->getConnection();
$contacts = (new ContactInfoRepository($pdo))->findAll();
$hours = new OpeningHoursRepository($pdo);
$weekdays = $hours->weekdays();
$specialDates = $hours->specialDates();

$dayNames = [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday',
             'Friday', 'Saturday', 'Sunday'];

$pageTitle = 'Contact';
$currentPage = 'contact';

require __DIR__ . '/templates/layout/header.php';
?>
<section class="w-full max-w-[1000px] mx-auto px-margin-mobile md:px-margin pt-36 pb-space-xl">
    <h1 class="text-4xl font-bold mb-8">Contact & opening hours</h1>

    <div class="grid gap-8 md:grid-cols-2">
        <section>
            <h2 class="text-2xl font-bold mb-4">Contact us</h2>
            <?php if ($contacts === []): ?>
                <p>Contact details coming soon.</p>
            <?php endif; ?>

            <?php foreach ($contacts as $contact): ?>
                <div class="mb-4 rounded-xl bg-surface-container-low p-4">
                    <h3 class="font-bold"><?= e($contact['label']) ?></h3>
                    <p class="whitespace-pre-line"><?= e($contact['value']) ?></p>
                </div>
            <?php endforeach; ?>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-4">Regular hours</h2>
            <?php foreach ($dayNames as $number => $name): ?>
                <?php $day = $weekdays[$number]; ?>
                <p class="py-2 border-b border-white/10 flex justify-between gap-4">
                    <span><?= e($name) ?></span>
                    <span>
                        <?php if ((int) $day['is_closed'] === 1): ?>
                            Closed
                        <?php else: ?>
                            <?= e(substr((string) $day['opens_at'], 0, 5)) ?>
                            –
                            <?= e(substr((string) $day['closes_at'], 0, 5)) ?>
                        <?php endif; ?>
                    </span>
                </p>
            <?php endforeach; ?>
        </section>
    </div>

    <?php if ($specialDates !== []): ?>
        <section class="mt-10">
            <h2 class="text-2xl font-bold mb-4">Special dates</h2>
            <?php foreach ($specialDates as $special): ?>
                <p class="py-2 border-b border-white/10">
                    <?= e($special['special_date']) ?> —
                    <?= e($special['label']) ?>:
                    <?php if ((int) $special['is_closed'] === 1): ?>
                        Closed
                    <?php else: ?>
                        <?= e(substr((string) $special['opens_at'], 0, 5)) ?>
                        –
                        <?= e(substr((string) $special['closes_at'], 0, 5)) ?>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/templates/layout/footer.php'; ?>
