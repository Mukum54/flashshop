<?php
/**
 * product.php — Product Detail Page
 * FlashShop Electronics
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$productId = cleanInt($_GET['id'] ?? 0, 1);

// Fetch product — only active ones
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.id AS cat_id
    FROM products p
    JOIN category c ON p.category_id = c.id
    WHERE p.id = ? AND p.is_active = 1
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="empty-state" style="padding:100px 24px;">
            <div class="empty-state__icon">🔍</div>
            <h2>Product Not Found</h2>
            <p>This product may have been removed or is no longer available.</p>
            <a href="/shop.php" class="btn btn--primary">Browse All Products</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Related products (same category)
$related = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN category c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    ORDER BY p.is_featured DESC, RAND()
    LIMIT 4
");
$related->execute([$product['cat_id'], $product['id']]);
$relatedProducts = $related->fetchAll();

$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
<div class="product-detail">

    <!-- Image -->
    <div class="product-detail__img-wrap">
        <img
            src="<?= e($product['image_url'] ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800') ?>"
            alt="<?= e($product['name']) ?>"
            class="product-detail__img"
        >
    </div>

    <!-- Info -->
    <div class="product-detail__info">
        <nav class="product-detail__breadcrumb" aria-label="Breadcrumb">
            <a href="/index.php">Home</a> /
            <a href="/shop.php">Shop</a> /
            <a href="/shop.php?category=<?= $product['cat_id'] ?>"><?= e($product['category_name']) ?></a>
        </nav>

        <span class="product-detail__cat-badge"><?= e($product['category_name']) ?></span>

        <h1 class="product-detail__name"><?= e($product['name']) ?></h1>

        <div class="product-detail__price"><?= formatPrice($product['price']) ?></div>

        <?php if ($product['stock'] > 5): ?>
        <div class="product-detail__stock">In Stock (<?= $product['stock'] ?> available)</div>
        <?php elseif ($product['stock'] > 0): ?>
        <div class="product-detail__stock product-detail__stock--low">⚠️ Low Stock — <?= $product['stock'] ?> left</div>
        <?php else: ?>
        <div class="product-detail__stock product-detail__stock--out">Out of Stock</div>
        <?php endif; ?>

        <p class="product-detail__desc"><?= nl2br(e($product['description'] ?? '')) ?></p>

        <?php if ($product['stock'] > 0): ?>
        <form action="/cart.php" method="POST">
            <input type="hidden" name="csrf_token"  value="<?= generateCsrf() ?>">
            <input type="hidden" name="action"      value="add">
            <input type="hidden" name="product_id"  value="<?= $product['id'] ?>">
            <div class="qty-form">
                <div class="qty-input" aria-label="Quantity selector">
                    <button type="button" class="qty-btn" id="qty-minus" aria-label="Decrease quantity">−</button>
                    <input type="number" name="quantity" id="qty" value="1"
                           min="1" max="<?= min($product['stock'], 99) ?>" aria-label="Quantity">
                    <button type="button" class="qty-btn" id="qty-plus" aria-label="Increase quantity">+</button>
                </div>
                <button type="submit" class="btn btn--primary btn--lg" style="flex:1;">
                    🛒 Add to Cart
                </button>
            </div>
        </form>

        <!-- WhatsApp direct order -->
        <div style="margin-top:14px;">
            <?php
            // Build single-product WhatsApp URL
            $waItems = [['name' => $product['name'], 'quantity' => 1, 'price' => $product['price']]];
            $waUrl   = buildWhatsAppUrl($waItems, $product['price'], 'DIRECT');
            ?>
            <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener" class="btn btn--whatsapp btn--full">
                💬 Order via WhatsApp
            </a>
        </div>

        <?php else: ?>
        <div style="margin-top:16px;">
            <button class="btn btn--ghost btn--full" disabled>Out of Stock</button>
            <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener"
               class="btn btn--whatsapp btn--full" style="margin-top:10px;">
                💬 Notify Me via WhatsApp
            </a>
        </div>
        <?php endif; ?>

        <!-- Trust badges -->
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:28px;padding-top:24px;border-top:1px solid var(--border);font-size:0.82rem;color:var(--muted);">
            <span>🔒 Secure Checkout</span>
            <span>✅ 100% Genuine</span>
            <span>↩️ 7-Day Returns</span>
            <span>⚡ Fast Delivery</span>
        </div>
    </div>
</div>
</div>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2>You May Also Like</h2>
            <p>More from <?= e($product['category_name']) ?></p>
            <div class="section-header__line"></div>
        </div>
        <div class="products-grid">
            <?php foreach ($relatedProducts as $rp): ?>
            <div class="product-card">
                <a href="/product.php?id=<?= $rp['id'] ?>" class="product-card__img-wrap" tabindex="-1">
                    <img src="<?= e($rp['image_url'] ?: '') ?>" alt="<?= e($rp['name']) ?>"
                         class="product-card__img" loading="lazy">
                    <span class="product-card__badge"><?= e($rp['category_name']) ?></span>
                </a>
                <div class="product-card__body">
                    <h3 class="product-card__name">
                        <a href="/product.php?id=<?= $rp['id'] ?>"><?= e($rp['name']) ?></a>
                    </h3>
                    <div class="product-card__price"><?= formatPrice($rp['price']) ?></div>
                </div>
                <div class="product-card__footer">
                    <a href="/product.php?id=<?= $rp['id'] ?>" class="btn btn--secondary btn--sm btn--full">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Quantity stepper
(function() {
    var input  = document.getElementById('qty');
    var btnMin = document.getElementById('qty-minus');
    var btnPls = document.getElementById('qty-plus');
    if (!input) return;
    var max = parseInt(input.max) || 99;
    btnMin.addEventListener('click', function() { if (input.value > 1) input.value--; });
    btnPls.addEventListener('click', function() { if (input.value < max) input.value++; });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
