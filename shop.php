<?php
/**
 * shop.php — Product Listing & Search
 * FlashShop Electronics
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ── Input sanitisation ────────────────────────────────────
$categoryId = cleanInt($_GET['category'] ?? 0);
$search     = cleanInput($_GET['q'] ?? '', 200);
$page       = cleanInt($_GET['page'] ?? 1, 1, 1000);
$featured   = cleanInt($_GET['featured'] ?? 0, 0, 1);
$perPage    = 12;
$pg         = paginate($page, $perPage);

// ── Build query ────────────────────────────────────────────
$where   = ['p.is_active = 1'];
$params  = [];

if ($categoryId > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $where[]  = 'MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $search . '*';
}
if ($featured) {
    $where[] = 'p.is_featured = 1';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereClause");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

// Products
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN category c ON p.category_id = c.id
    $whereClause
    ORDER BY p.is_featured DESC, p.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $pg['limit'];
$params[] = $pg['offset'];
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── Sidebar categories ─────────────────────────────────────
$cats = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM category c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
    GROUP BY c.id ORDER BY c.id
")->fetchAll();

// ── Active category name ────────────────────────────────────
$activeCatName = 'All Products';
if ($categoryId > 0) {
    foreach ($cats as $c) {
        if ((int)$c['id'] === $categoryId) {
            $activeCatName = $c['name'];
            break;
        }
    }
}

// ── Build base URL for pagination ──────────────────────────
$baseParams = [];
if ($categoryId) $baseParams[] = 'category=' . $categoryId;
if ($search)     $baseParams[] = 'q=' . urlencode($search);
if ($featured)   $baseParams[] = 'featured=1';
$baseUrl = '/shop.php' . ($baseParams ? '?' . implode('&', $baseParams) : '');

$pageTitle = $search ? 'Search: ' . $search : $activeCatName;
include __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><?= e($featured ? 'Hot Deals' : ($search ? 'Results for "' . $search . '"' : $activeCatName)) ?></h1>
        <nav class="breadcrumb-trail">
            <a href="/index.php">Home</a> <span>/</span>
            <a href="/shop.php">Shop</a>
            <?php if ($categoryId): ?> <span>/</span> <?= e($activeCatName) ?><?php endif; ?>
            <?php if ($search): ?> <span>/</span> Search<?php endif; ?>
        </nav>
    </div>
</div>

<div class="container" style="padding-top:40px; padding-bottom:60px;">
<div class="shop-layout">

    <!-- Sidebar -->
    <aside class="shop-sidebar">
        <h3>Categories</h3>
        <div class="filter-list">
            <a href="/shop.php" class="filter-item <?= ($categoryId === 0 && !$search && !$featured) ? 'filter-item--active' : '' ?>">
                All Products
                <span class="filter-item__count"><?= array_sum(array_column($cats, 'product_count')) ?></span>
            </a>
            <?php foreach ($cats as $cat): ?>
            <a href="/shop.php?category=<?= $cat['id'] ?>"
               class="filter-item <?= ((int)$cat['id'] === $categoryId) ? 'filter-item--active' : '' ?>">
                <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
                <span class="filter-item__count"><?= $cat['product_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top:28px;">Filter</h3>
        <div class="filter-list">
            <a href="/shop.php?featured=1" class="filter-item <?= $featured ? 'filter-item--active' : '' ?>">
                🔥 Hot Deals
            </a>
        </div>

        <!-- Search -->
        <h3 style="margin-top:28px;">Search</h3>
        <form action="/shop.php" method="GET">
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products…"
                   class="form-control" style="margin-top:8px;">
            <button type="submit" class="btn btn--primary btn--sm btn--full" style="margin-top:10px;">Search</button>
        </form>
    </aside>

    <!-- Products -->
    <div>
        <div class="shop-main-header">
            <p class="shop-result-count">
                <?= number_format($totalItems) ?> product<?= $totalItems !== 1 ? 's' : '' ?> found
            </p>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-state__icon">🔍</div>
            <h2>No products found</h2>
            <p>Try a different category or search term.</p>
            <a href="/shop.php" class="btn btn--primary">View All Products</a>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <a href="/product.php?id=<?= $product['id'] ?>" class="product-card__img-wrap" tabindex="-1">
                    <img
                        src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600') ?>"
                        alt="<?= e($product['name']) ?>"
                        class="product-card__img"
                        loading="lazy"
                    >
                    <span class="product-card__badge"><?= e($product['category_name']) ?></span>
                    <?php if ($product['is_featured']): ?>
                    <span class="product-card__featured">🔥 Hot</span>
                    <?php endif; ?>
                </a>
                <div class="product-card__body">
                    <h3 class="product-card__name">
                        <a href="/product.php?id=<?= $product['id'] ?>"><?= e($product['name']) ?></a>
                    </h3>
                    <div class="product-card__price"><?= formatPrice($product['price']) ?></div>
                </div>
                <div class="product-card__footer">
                    <div class="product-card__actions">
                        <?php if ($product['stock'] > 0): ?>
                        <form action="/cart.php" method="POST" style="flex:1;">
                            <input type="hidden" name="csrf_token"  value="<?= generateCsrf() ?>">
                            <input type="hidden" name="action"      value="add">
                            <input type="hidden" name="product_id"  value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity"    value="1">
                            <button type="submit" class="btn btn--primary btn--sm btn--full">🛒 Add to Cart</button>
                        </form>
                        <?php else: ?>
                        <span class="badge badge--error" style="flex:1;justify-content:center;padding:8px;">Out of Stock</span>
                        <?php endif; ?>
                        <a href="/product.php?id=<?= $product['id'] ?>" class="product-card__link">Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php renderPagination($page, $totalItems, $perPage, $baseUrl); ?>
        <?php endif; ?>
    </div>

</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
