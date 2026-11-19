<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new ShowingRepository($pdo);
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['screening_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false || $id === null) {
            http_response_code(400);
            $error = 'Invalid showing ID.';
        } else {
            try {
                if ($action === 'delete') {
                    if (!$repository->delete($id)) {
                        $error = 'Showing not found.';
                    } else {
                        admin_flash('Showing deleted.');
                    }
                } elseif ($action === 'feature') {
                    $repository->feature($id);
                    admin_flash('Showing featured on its date.');
                } elseif ($action === 'unfeature') {
                    $repository->unfeature($id);
                    admin_flash('Showing removed from the front page.');
                } else {
                    http_response_code(400);
                    $error = 'Unknown action.';
                }

                if ($error === null) {
                    header(
                        'Location: ' . url('admin/showings.php'),
                        true,
                        303
                    );
                    exit;
                }
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}

$showings = $repository->listForAdmin();
$flash = admin_take_flash();
$pageTitle = 'Manage showings';
$adminPage = 'showings';

require __DIR__ . '/../templates/admin/header.php';
?>
<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <h1 class="text-3xl font-bold">Showings</h1>
    <a href="<?= e(url('admin/showing_edit.php')) ?>"
       class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        Add showing
    </a>
</div>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-5 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<p class="mb-5 text-on-surface-variant">
    A showing with bookings cannot be deleted or moved. Featured showings
    appear on the public homepage for their scheduled day.
</p>

<div class="overflow-x-auto rounded-xl bg-surface-container-low">
    <table class="w-full text-left">
        <thead>
            <tr class="border-b border-white/10">
                <th class="p-3">Movie</th>
                <th class="p-3">Auditorium</th>
                <th class="p-3">Date / UTC time</th>
                <th class="p-3">Price</th>
                <th class="p-3">Seats left</th>
                <th class="p-3">Featured</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($showings as $showing): ?>
                <tr class="border-b border-white/10">
                    <td class="p-3"><?= e($showing['movie_title']) ?></td>
                    <td class="p-3"><?= e($showing['hall_name']) ?></td>
                    <td class="p-3">
                        <?= e($showing['screening_date']) ?>
                        <?= e(substr($showing['screening_time'], 0, 5)) ?>
                    </td>
                    <td class="p-3"><?= (int) $showing['price_kr'] ?> kr</td>
                    <td class="p-3">
                        <?= (int) $showing['seats_left'] ?>
                        / <?= (int) $showing['total_seats'] ?>
                    </td>
                    <td class="p-3">
                        <?= (int) $showing['featured'] === 1 ? 'Yes' : 'No' ?>
                    </td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-3">
                            <a class="underline"
                               href="<?= e(url(
                                   'admin/showing_edit.php?id=' .
                                   (int) $showing['screening_id']
                               )) ?>">Edit</a>

                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="screening_id"
                                       value="<?= (int) $showing['screening_id'] ?>">
                                <input type="hidden" name="action"
                                       value="<?= (int) $showing['featured'] === 1
                                           ? 'unfeature'
                                           : 'feature' ?>">
                                <button class="underline">
                                    <?= (int) $showing['featured'] === 1
                                        ? 'Unfeature'
                                        : 'Feature' ?>
                                </button>
                            </form>

                            <form method="post"
                                  onsubmit="return confirm('Delete this showing?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="screening_id"
                                       value="<?= (int) $showing['screening_id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="text-red-400">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../templates/admin/footer.php'; ?>