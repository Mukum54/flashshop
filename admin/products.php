<?php
/**
 * admin/products.php — List All Products
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p JOIN category c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Products | FlashShop Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<div class="admin-wrapper">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
<div class="admin-topbar">
    <div class="admin-topbar__title">Products</div>
    <div class="admin-topbar__actions">
        <a href="/admin/product-add.php" class="btn btn--primary btn--sm">+ Add Product</a>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>
<div class="admin-card">
    <div class="admin-card__header">
        <h3>All Products (<?= count($products) ?>)</h3>
    </div>
    <table class="admin-table">
        <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><img src="<?= e($p['image_url'] ?: '') ?>" alt="<?= e($p['name']) ?>"></td>
            <td><strong><?= e($p['name']) ?></strong></td>
            <td><span class="badge badge--navy"><?= e($p['category_name']) ?></span></td>
            <td><strong><?= formatPrice($p['price']) ?></strong></td>
            <td><?= $p['stock'] > 0 ? '<span class="badge badge--success">'.$p['stock'].'</span>' : '<span class="badge badge--error">OOS</span>' ?></td>
            <td>
                <div class="action-links">
                    <a href="/admin/product-edit.php?id=<?= $p['id'] ?>" class="action-link action-link--edit">✏ Edit</a>
                    <form method="POST" action="/admin/product-delete.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button class="action-link action-link--delete" data-confirm="Delete '<?= e($p['name']) ?>'?">🗑 Delete</button>
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
</body></html>
