<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new OpeningHoursRepository($pdo);
$names = [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday',
          'Friday', 'Saturday', 'Sunday'];
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $validTime = static function ($value): bool {
            return is_string($value)
                && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value) === 1;
        };
        $validDate = static function ($value): bool {
            if (!is_string($value)) {
                return false;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $date !== false && $date->format('Y-m-d') === $value;
        };

        if ($action === 'weekdays') {
            $submitted = $_POST['days'] ?? null;
            $days = [];

            if (!is_array($submitted)) {
                $error = 'Invalid weekday data.';
            } else {
                foreach ($names as $number => $name) {
                    $entry = $submitted[$number] ?? null;
                    if (!is_array($entry)) {
                        $error = 'Missing hours for ' . $name . '.';
                        break;
                    }

                    $closed = isset($entry['closed']) ? 1 : 0;
                    $opens = $entry['opens'] ?? null;
                    $closes = $entry['closes'] ?? null;

                    if (!$closed && (
                        !$validTime($opens)
                        || !$validTime($closes)
                        || $opens >= $closes
                    )) {
                        $error = $name . ': opening must be earlier than closing.';
                        break;
                    }

                    $days[$number] = [
                        'opens_at' => $closed ? null : $opens,
                        'closes_at' => $closed ? null : $closes,
                        'is_closed' => $closed,
                    ];
                }
            }

            if ($error === null) {
                $repository->saveWeekdays($days);
                admin_flash('Weekly hours saved.');
                header('Location: ' . url('admin/hours.php'), true, 303);
                exit;
            }
        } elseif ($action === 'save_special') {
            $rawId = $_POST['id'] ?? '';
            $id = $rawId === '' ? null : filter_var(
                $rawId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            $date = $_POST['date'] ?? null;
            $label = $_POST['label'] ?? null;
            $closed = isset($_POST['closed']) ? 1 : 0;
            $opens = $_POST['opens'] ?? null;
            $closes = $_POST['closes'] ?? null;

            if ($id === false
                || ($id !== null && $repository->findSpecial($id) === null)
                || !$validDate($date)
                || !is_string($label)
                || trim($label) === ''
                || mb_strlen(trim($label)) > 100
                || (!$closed && (
                    !$validTime($opens)
                    || !$validTime($closes)
                    || $opens >= $closes
                ))) {
                $error = 'Check the date, label and opening times.';
            } else {
                try {
                    $repository->saveSpecial(
                        $id,
                        $date,
                        trim($label),
                        $closed ? null : $opens,
                        $closed ? null : $closes,
                        $closed
                    );
                    admin_flash('Special date saved.');
                    header('Location: ' . url('admin/hours.php'), true, 303);
                    exit;
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                    $error = 'That date already has an entry.';
                }
            }
        } elseif ($action === 'delete_special') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($id === false || $id === null
                || $repository->findSpecial($id) === null) {
                $error = 'Special date not found.';
            } else {
                $repository->deleteSpecial($id);
                admin_flash('Special date deleted.');
                header('Location: ' . url('admin/hours.php'), true, 303);
                exit;
            }
        } else {
            http_response_code(400);
            $error = 'Unknown action.';
        }
    }
}

$days = $repository->weekdays();
$specialDates = $repository->specialDates();
$flash = admin_take_flash();
$pageTitle = 'Opening hours';
$adminPage = 'hours';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-4">Opening hours</h1>
<p class="mb-6 text-on-surface-variant">
    Set the seven regular days and any exceptions, such as Christmas Eve.
</p>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-4 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<h2 class="text-xl font-bold mb-4">Regular week</h2>
<form method="post" class="space-y-3 mb-12">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="weekdays">

    <?php foreach ($names as $number => $name): ?>
        <?php $day = $days[$number]; ?>
        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-surface-container-low p-4">
            <strong class="w-28"><?= e($name) ?></strong>
            <label>Open
                <input type="time" name="days[<?= $number ?>][opens]"
                       value="<?= e(substr((string) $day['opens_at'], 0, 5)) ?>"
                       class="bg-surface-container-high p-2 rounded">
            </label>
            <label>Close
                <input type="time" name="days[<?= $number ?>][closes]"
                       value="<?= e(substr((string) $day['closes_at'], 0, 5)) ?>"
                       class="bg-surface-container-high p-2 rounded">
            </label>
            <label>
                <input type="checkbox" name="days[<?= $number ?>][closed]"
                       <?= (int) $day['is_closed'] === 1 ? 'checked' : '' ?>>
                Closed
            </label>
        </div>
    <?php endforeach; ?>

    <button class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        Save regular hours
    </button>
</form>

<h2 class="text-xl font-bold mb-4">Add special date</h2>
<form method="post" class="flex flex-wrap items-end gap-3 mb-10">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_special">
    <label>Date
        <input type="date" name="date" required
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <label>Label
        <input name="label" required maxlength="100" placeholder="Christmas Eve"
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <label>Open
        <input type="time" name="opens"
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <label>Close
        <input type="time" name="closes"
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <label><input type="checkbox" name="closed"> Closed</label>
    <button class="rounded-xl bg-primary-container px-4 py-2 text-on-primary">
        Add date
    </button>
</form>

<h2 class="text-xl font-bold mb-4">Special dates</h2>
<?php if ($specialDates === []): ?>
    <p>No special dates yet.</p>
<?php endif; ?>

<?php foreach ($specialDates as $special): ?>
    <div class="rounded-xl bg-surface-container-low p-4 mb-4">
        <form method="post" class="flex flex-wrap items-end gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_special">
            <input type="hidden" name="id"
                   value="<?= (int) $special['special_hours_id'] ?>">
            <label>Date
                <input type="date" name="date" required
                       value="<?= e($special['special_date']) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <label>Label
                <input name="label" required maxlength="100"
                       value="<?= e($special['label']) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <label>Open
                <input type="time" name="opens"
                       value="<?= e(substr((string) $special['opens_at'], 0, 5)) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <label>Close
                <input type="time" name="closes"
                       value="<?= e(substr((string) $special['closes_at'], 0, 5)) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <label>
                <input type="checkbox" name="closed"
                       <?= (int) $special['is_closed'] === 1 ? 'checked' : '' ?>>
                Closed
            </label>
            <button class="rounded-xl bg-primary-container px-4 py-2 text-on-primary">
                Save
            </button>
        </form>

        <form method="post" class="mt-3"
              onsubmit="return confirm('Delete this special date?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_special">
            <input type="hidden" name="id"
                   value="<?= (int) $special['special_hours_id'] ?>">
            <button class="text-red-400 hover:underline">Delete</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>