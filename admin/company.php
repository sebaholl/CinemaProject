<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$repository = new CompanyRepository($pdo);
$company = $repository->find();
$error = null;
$description = (string) ($company['description'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check()) {
        http_response_code(400);
        $error = 'Form expired. Refresh and try again.';
    } else {
        $description = is_string($_POST['description'] ?? null)
            ? trim($_POST['description'])
            : '';

        if ($description === '' || mb_strlen($description) > 5000) {
            $error = 'Enter a description of 1–5000 characters.';
        } else {
            $repository->saveDescription($description);
            admin_flash('Company description saved.');
            header('Location: ' . url('admin/company.php'), true, 303);
            exit;
        }
    }
}

$flash = admin_take_flash();
$pageTitle = 'Company description';
$adminPage = 'company';

require __DIR__ . '/../templates/admin/header.php';
?>
<h1 class="text-3xl font-bold mb-4">Company description</h1>
<p class="mb-6 text-on-surface-variant">
    This text appears on the public home page.
</p>

<?php if ($error !== null): ?>
    <p role="alert" class="mb-4 text-red-400"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" class="max-w-3xl space-y-4">
    <?= csrf_field() ?>
    <label for="description" class="block">Cinema description</label>
    <textarea id="description" name="description" required maxlength="5000"
              rows="8" class="w-full rounded-xl bg-surface-container-low p-4"><?= e($description) ?></textarea>
    <button class="rounded-xl bg-primary-container px-5 py-3 text-on-primary">
        Save description
    </button>
</form>
<?php require __DIR__ . '/../templates/admin/footer.php'; ?>