<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new ContactInfoRepository($pdo);
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($action === 'delete') {
            if ($id === false || $id === null
                || $repository->find($id) === null) {
                $error = 'Contact entry not found.';
            } else {
                $repository->delete($id);
                admin_flash('Contact entry deleted.');
                header('Location: ' . url('admin/contact.php'), true, 303);
                exit;
            }
        } elseif ($action === 'create' || $action === 'update') {
            $kind = $_POST['kind'] ?? null;
            $label = $_POST['label'] ?? null;
            $value = $_POST['value'] ?? null;

            if (!is_string($kind)
                || !in_array($kind, ['address', 'phone', 'email'], true)
                || !is_string($label)
                || trim($label) === ''
                || mb_strlen(trim($label)) > 80
                || !is_string($value)
                || trim($value) === ''
                || mb_strlen(trim($value)) > 255
                || ($kind === 'email'
                    && (strlen(trim($value)) > 254
                        || !filter_var(trim($value), FILTER_VALIDATE_EMAIL)))
                || ($kind === 'phone'
                    && (strlen(trim($value)) > 40
                        || !preg_match('/^\+?[0-9() .-]{5,40}$/', trim($value))))
                || ($kind === 'address' && mb_strlen(trim($value)) < 4)
                || ($action === 'update'
                    && ($id === false || $id === null
                        || $repository->find($id) === null))) {
                $error = 'Check the type, label and contact value.';
            } else {
                if ($action === 'create') {
                    $repository->create($kind, trim($label), trim($value));
                } else {
                    $repository->update($id, $kind, trim($label), trim($value));
                }

                admin_flash('Contact information saved.');
                header('Location: ' . url('admin/contact.php'), true, 303);
                exit;
            }
        } else {
            http_response_code(400);
            $error = 'Unknown action.';
        }
    }
}

$contacts = $repository->findAll();
$flash = admin_take_flash();
$pageTitle = 'Contact information';
$adminPage = 'contact';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-4">Contact information</h1>
<p class="text-on-surface-variant mb-6">
    These entries appear on the public contact page and in the footer.
</p>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-4 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<h2 class="text-xl font-bold mb-4">Add entry</h2>
<form method="post" class="flex flex-wrap items-end gap-3 mb-10">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Type
        <select name="kind" class="block bg-surface-container-high p-2 rounded">
            <option value="address">Address</option>
            <option value="phone">Phone</option>
            <option value="email">Email</option>
        </select>
    </label>
    <label>Label
        <input name="label" required maxlength="80"
               placeholder="Box office"
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <label>Value
        <input name="value" required maxlength="255"
               class="block bg-surface-container-high p-2 rounded">
    </label>
    <button class="rounded-xl bg-primary-container px-4 py-2 text-on-primary">
        Add
    </button>
</form>

<h2 class="text-xl font-bold mb-4">Existing entries</h2>
<?php if ($contacts === []): ?>
    <p>No contact entries yet.</p>
<?php endif; ?>

<?php foreach ($contacts as $contact): ?>
    <div class="rounded-xl bg-surface-container-low p-4 mb-4">
        <form method="post" class="flex flex-wrap items-end gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id"
                   value="<?= (int) $contact['contact_info_id'] ?>">
            <label>Type
                <select name="kind"
                        class="block bg-surface-container-high p-2 rounded">
                    <?php foreach (['address', 'phone', 'email'] as $kind): ?>
                        <option value="<?= e($kind) ?>"
                            <?= $contact['kind'] === $kind ? 'selected' : '' ?>>
                            <?= e(ucfirst($kind)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Label
                <input name="label" required maxlength="80"
                       value="<?= e($contact['label']) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <label>Value
                <input name="value" required maxlength="255"
                       value="<?= e($contact['value']) ?>"
                       class="block bg-surface-container-high p-2 rounded">
            </label>
            <button class="rounded-xl bg-primary-container px-4 py-2 text-on-primary">
                Save
            </button>
        </form>

        <form method="post" class="mt-3"
              onsubmit="return confirm('Delete this contact entry?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id"
                   value="<?= (int) $contact['contact_info_id'] ?>">
            <button class="text-red-400 hover:underline">Delete</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../templates/admin/footer.php'; ?>