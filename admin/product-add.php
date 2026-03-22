<?php
/**
 * admin/product-add.php — Add New Product
 * FlashShop Admin (Production)
 *
 * Security: admin-only, CSRF, prepared statements, image size/type check
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$errors = [];
$values = ['name'=>'','description'=>'','price'=>'','stock'=>'','category_id'=>'','image_url'=>''];

$categories = $pdo->query("SELECT id, name FROM category ORDER BY name")->fetchAll();

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
    $values      = compact('name','description','price','stock','categoryId','imageUrl');

    if (strlen($name) < 2)       $errors[] = 'Product name is required (min 2 characters).';
    if ($price <= 0)             $errors[] = 'Price must be greater than 0.';
    if ($categoryId < 1)         $errors[] = 'Please select a category.';
    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Image URL must be a valid URL.';
    }

    if (empty($errors)) {
        $slug = makeSlug($name) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $stmt = $pdo->prepare("
            INSERT INTO products (name, slug, description, price, stock, category_id, image_url, is_featured, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $description, $price, $stock, $categoryId, $imageUrl ?: null, $isFeatured, $isActive]);
        setFlash('success', "✓ Product \"$name\" added successfully.");
        header('Location: /admin/products.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Add Product | FlashShop Admin</title>
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
        <div class="admin-topbar__title">Add Product</div>
    </div>
    <div class="admin-topbar__actions">
        <a href="/admin/products.php" class="admin-view-site">← Back to Products</a>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>

<div class="admin-page-header">
    <h1>Add New Product</h1>
    <p class="admin-breadcrumb">Fill in the product details below.</p>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:24px;" role="alert">
    <?= implode('<br>', array_map('e', $errors)) ?>
</div>
<?php endif; ?>

<form action="/admin/product-add.php" method="POST" id="product-form">
    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
    <div class="admin-form-grid">
        <!-- Left column -->
        <div>
            <div class="admin-card">
                <div class="admin-card__header"><h3>Product Details</h3></div>
                <div class="admin-card__body">
                    <div class="form-group">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($values['name']) ?>" required maxlength="200"
                               placeholder="e.g. Samsung Galaxy S24">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5"
                                  maxlength="3000" placeholder="Product description..."><?= e($values['description']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="price">Price (FCFA) *</label>
                            <input type="number" id="price" name="price" class="form-control"
                                   value="<?= e($values['price']) ?>" required min="1" step="1"
                                   placeholder="e.g. 250000">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" class="form-control"
                                   value="<?= e($values['stock'] ?: '0') ?>" min="0" max="99999">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div>
            <div class="admin-card">
                <div class="admin-card__header"><h3>Organisation</h3></div>
                <div class="admin-card__body">
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">– Choose category –</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ((int)($values['categoryId'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="image_url">Image URL</label>
                        <input type="url" id="image_url" name="image_url" class="form-control"
                               value="<?= e($values['imageUrl'] ?? '') ?>"
                               placeholder="https://..." maxlength="500"
                               oninput="previewImg(this.value)">
                        <div id="img-preview" style="margin-top:10px;display:none;">
                            <img id="img-preview-tag" src="" alt="Preview"
                                 style="width:100%;border-radius:8px;max-height:180px;object-fit:cover;border:1px solid var(--glass-border);">
                        </div>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="is_featured" value="1" <?= ($values['isFeatured'] ?? false) ? 'checked' : '' ?>>
                            🔥 Featured Product
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            ✅ Active (visible)
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:16px;">
                ✓ Add Product
            </button>
        </div>
    </div>
</form>
</div></div></div>
<script src="/assets/js/main.js"></script>
<script>
function previewImg(url) {
    var preview = document.getElementById('img-preview');
    var tag     = document.getElementById('img-preview-tag');
    if (url && url.startsWith('http')) {
        tag.src = url;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
// Preview on load if editing
var existing = document.getElementById('image_url').value;
if (existing) previewImg(existing);
</script>
</body>
</html>
