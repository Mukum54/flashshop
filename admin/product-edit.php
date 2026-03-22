<?php
/**
 * admin/product-edit.php — Edit Existing Product
 * FlashShop Admin (Production)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$productId = cleanInt($_GET['id'] ?? 0, 1);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    header('Location: /admin/products.php'); exit;
}

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $name        = cleanInput($_POST['name']        ?? '', 200);
    $description = cleanInput($_POST['description'] ?? '', 3000);
    $price       = (float) ($_POST['price']         ?? 0);
    $stock       = cleanInt($_POST['stock']         ?? 0, 0);
    $categoryId  = cleanInt($_POST['category_id']   ?? 0, 1);
    $imageUrl    = cleanInput($_POST['image_url']   ?? '', 500);
    $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
    $isActive    = isset($_POST['is_active'])   ? 1 : 0;

    if (strlen($name) < 2)   $errors[] = 'Product name is required.';
    if ($price <= 0)          $errors[] = 'Price must be greater than 0.';
    if ($categoryId < 1)      $errors[] = 'Please select a category.';
    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Image URL must be a valid URL.';
    }

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE products
            SET name=?, description=?, price=?, stock=?, category_id=?, image_url=?, is_featured=?, is_active=?
            WHERE id=?
        ")->execute([$name, $description, $price, $stock, $categoryId, $imageUrl ?: null, $isFeatured, $isActive, $productId]);

        setFlash('success', "✓ Product \"$name\" updated.");
        header('Location: /admin/products.php'); exit;
    }

    // Repopulate $product with posted values for re-render
    $product = array_merge($product, [
        'name'=>$name, 'description'=>$description, 'price'=>$price,
        'stock'=>$stock, 'category_id'=>$categoryId, 'image_url'=>$imageUrl,
        'is_featured'=>$isFeatured, 'is_active'=>$isActive
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Edit Product | FlashShop Admin</title>
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
        <div class="admin-topbar__title">Edit Product</div>
    </div>
    <div class="admin-topbar__actions">
        <a href="/admin/products.php" class="admin-view-site">← Back to Products</a>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>

<div class="admin-page-header">
    <h1>Editing: <?= e($product['name']) ?></h1>
    <p class="admin-breadcrumb">Product ID: #<?= $productId ?></p>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:24px;" role="alert">
    <?= implode('<br>', array_map('e', $errors)) ?>
</div>
<?php endif; ?>

<form action="/admin/product-edit.php?id=<?= $productId ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <div class="admin-form-grid">
        <div>
            <div class="admin-card">
                <div class="admin-card__header"><h3>Product Details</h3></div>
                <div class="admin-card__body">
                    <div class="form-group">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($product['name']) ?>" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"
                                  rows="5" maxlength="3000"><?= e($product['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="price">Price (FCFA) *</label>
                            <input type="number" id="price" name="price" class="form-control"
                                   value="<?= e($product['price']) ?>" required min="1" step="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" class="form-control"
                                   value="<?= e($product['stock']) ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="admin-card__header"><h3>Organisation</h3></div>
                <div class="admin-card__body">
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">– Choose category –</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="image_url">Image URL</label>
                        <input type="url" id="image_url" name="image_url" class="form-control"
                               value="<?= e($product['image_url'] ?? '') ?>"
                               placeholder="https://..." maxlength="500"
                               oninput="previewImg(this.value)">
                        <div id="img-preview" style="margin-top:10px;">
                            <?php if (!empty($product['image_url'])): ?>
                            <img id="img-preview-tag" src="<?= e($product['image_url']) ?>" alt="Preview"
                                 style="width:100%;border-radius:8px;max-height:180px;object-fit:cover;border:1px solid var(--glass-border);">
                            <?php else: ?>
                            <img id="img-preview-tag" src="" alt="Preview"
                                 style="width:100%;border-radius:8px;max-height:180px;object-fit:cover;border:1px solid var(--glass-border);display:none;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                            🔥 Featured
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?>>
                            ✅ Active
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:16px;">
                ✓ Save Changes
            </button>
            <a href="/shop.php" target="_blank"
               style="display:block;text-align:center;margin-top:10px;font-size:0.82rem;color:var(--muted);">
                ↗ Preview on storefront
            </a>
        </div>
    </div>
</form>
</div></div></div>
<script src="/assets/js/main.js"></script>
<script>
function previewImg(url) {
    var tag = document.getElementById('img-preview-tag');
    if (!tag) return;
    if (url && url.startsWith('http')) { tag.src = url; tag.style.display = 'block'; }
    else { tag.style.display = 'none'; }
}
</script>
</body>
</html>
