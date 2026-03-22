<?php
/**
 * admin/category-add.php — Add New Category
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$errors = [];
$values = ['name' => '', 'description' => '', 'icon' => '📦'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $name        = cleanInput($_POST['name']        ?? '', 100);
    $description = cleanInput($_POST['description'] ?? '', 500);
    $icon        = cleanInput($_POST['icon']        ?? '📦', 10);
    $values = compact('name', 'description', 'icon');

    if (strlen($name) < 2) $errors[] = 'Category name is required (min 2 characters).';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO category (name, description, icon) VALUES (?, ?, ?)")
            ->execute([$name, $description, $icon]);
        setFlash('success', "✓ Category \"$name\" added.");
        header('Location: /admin/categories.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Add Category | FlashShop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
<div class="admin-topbar">
    <div class="admin-topbar__left">
        <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
        <div class="admin-topbar__title">Add Category</div>
    </div>
    <div class="admin-topbar__actions">
        <a href="/admin/categories.php" class="admin-view-site">← Back</a>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>
<div class="admin-page-header">
    <h1>Add Category</h1>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:24px;"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div style="max-width:520px;">
<form action="/admin/category-add.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <div class="admin-card">
        <div class="admin-card__header"><h3>Category Details</h3></div>
        <div class="admin-card__body">
            <div class="form-group">
                <label class="form-label" for="icon">Emoji Icon</label>
                <input type="text" id="icon" name="icon" class="form-control"
                       value="<?= e($values['icon']) ?>" maxlength="10"
                       placeholder="e.g. 📱, 💻, 🎧" style="font-size:1.5rem;width:80px;">
                <p style="font-size:0.8rem;color:var(--muted);margin-top:4px;">Paste an emoji — it'll appear in the shop navigation.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="name">Category Name *</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="<?= e($values['name']) ?>" required maxlength="100"
                       placeholder="e.g. Smartphones">
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control"
                          rows="3" maxlength="500"><?= e($values['description']) ?></textarea>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:16px;">✓ Add Category</button>
</form>
</div>
</div></div></div>
<script src="/assets/js/main.js"></script>
</body>
</html>
