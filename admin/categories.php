<?php
/**
 * admin/categories.php — Category List
 * FlashShop Admin
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM category c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Categories | FlashShop Admin</title>
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
        <div class="admin-topbar__title">Categories</div>
    </div>
    <div class="admin-topbar__actions">
        <a href="/admin/category-add.php" class="btn btn--primary btn--sm">+ Add Category</a>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>

<div class="admin-page-header">
    <h1>Categories</h1>
    <p class="admin-breadcrumb"><?= count($categories) ?> categories total</p>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Description</th>
                <th>Products</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td style="font-size:1.5rem;text-align:center;"><?= e($cat['icon'] ?? '📦') ?></td>
            <td><strong><?= e($cat['name']) ?></strong></td>
            <td style="color:var(--muted);max-width:240px;font-size:0.85rem;">
                <?= e(mb_substr($cat['description'] ?? '', 0, 70)) ?><?= strlen($cat['description'] ?? '') > 70 ? '…' : '' ?>
            </td>
            <td>
                <span class="badge badge--amber"><?= $cat['product_count'] ?> products</span>
            </td>
            <td>
                <div class="action-links">
                    <a href="/admin/category-edit.php?id=<?= $cat['id'] ?>" class="action-link action-link--edit">✏ Edit</a>
                    <form method="POST" action="/admin/category-delete.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button class="action-link action-link--delete"
                                data-confirm="Delete '<?= e($cat['name']) ?>'? All its products will be deactivated!">
                            🗑 Delete
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div></div></div>
<script src="/assets/js/main.js"></script>
</body>
</html>
