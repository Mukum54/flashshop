<?php
/**
 * index.php — Homepage (Electronics Store)
 * FlashShop E-Commerce
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = 'Home';

// ── Featured products ──────────────────────────────────────
$featured = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN category c ON p.category_id = c.id
    WHERE p.is_featured = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll();

// If fewer than 4 featured, top up with latest
if (count($featured) < 4) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN category c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT 8
    ");
    $stmt->execute();
    $featured = $stmt->fetchAll();
}

// ── Categories with product count ─────────────────────────
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM category c
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
    GROUP BY c.id
    ORDER BY c.id
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero">
    <div class="container">
        <div class="hero__inner">
            <span class="hero__tag">⚡ Premium Electronics — Best Prices</span>
            <h1>The Future of<br><span>Electronics</span> Is <em>Here</em></h1>
            <p class="hero__sub">
                Smartphones, laptops, audio, gaming gear and smart home devices —
                curated by experts, ordered in seconds via WhatsApp.
            </p>
            <div class="hero__actions">
                <a href="/shop.php" class="btn btn--primary btn--lg">Shop Now →</a>
                <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="btn btn--whatsapp btn--lg">
                    💬 WhatsApp Order
                </a>
            </div>
            <div class="hero__stats">
                <div class="hero__stat">
                    <div class="hero__stat-val">200+</div>
                    <div class="hero__stat-lbl">Products</div>
                </div>
                <div class="hero__stat">
                    <div class="hero__stat-val">5k+</div>
                    <div class="hero__stat-lbl">Customers</div>
                </div>
                <div class="hero__stat">
                    <div class="hero__stat-val">24h</div>
                    <div class="hero__stat-lbl">Support</div>
                </div>
                <div class="hero__stat">
                    <div class="hero__stat-val">100%</div>
                    <div class="hero__stat-lbl">Genuine</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════ SHOP BY CATEGORY ═══════════════ -->
<section class="section section--sm">
    <div class="container">
        <div class="section-header">
            <h2>Shop by Category</h2>
            <p>Explore our full range of premium electronics</p>
            <div class="section-header__line"></div>
        </div>
        <div class="categories-strip">
            <?php foreach ($categories as $cat): ?>
            <a href="/shop.php?category=<?= $cat['id'] ?>" class="category-card">
                <div class="category-card__icon"><?= e($cat['icon']) ?></div>
                <div class="category-card__name"><?= e($cat['name']) ?></div>
                <div class="category-card__count"><?= $cat['product_count'] ?> products</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════ FEATURED PRODUCTS ═══════════════ -->
<section class="section section--alt">
    <div class="container">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Top-rated electronics at the best prices in Cameroon</p>
            <div class="section-header__line"></div>
        </div>

        <div class="products-grid">
            <?php foreach ($featured as $product): ?>
            <div class="product-card">
                <a href="/product.php?id=<?= $product['id'] ?>" class="product-card__img-wrap" tabindex="-1" aria-hidden="true">
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
                            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                            <input type="hidden" name="action"     value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity"   value="1">
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

        <div style="text-align:center; margin-top:56px;">
            <a href="/shop.php" class="btn btn--secondary btn--lg">View All Products →</a>
        </div>
    </div>
</section>

<!-- ═══════════════ WHATSAPP CTA ═══════════════ -->
<section class="section section--dark" style="text-align:center; padding:80px 0;">
    <div class="container" style="max-width:640px;">
        <div style="font-size:3.5rem; margin-bottom:20px;">💬</div>
        <h2 style="margin-bottom:16px;">Order Directly via WhatsApp</h2>
        <p style="color:var(--muted); margin-bottom:36px; font-size:1.05rem;">
            Add items to your cart, click <strong style="color:var(--whatsapp);">Order via WhatsApp</strong> and we'll confirm your order within minutes.
            Fast, simple and secure.
        </p>
        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="btn btn--whatsapp btn--lg">
            💬 Chat with Us: +237 654 492 653
        </a>
    </div>
</section>

<!-- ═══════════════ TRUST STRIP ═══════════════ -->
<section class="section section--sm">
    <div class="container">
        <div class="trust-strip">
            <?php
            $perks = [
                ['⚡', 'Fast Delivery',    'Nationwide delivery in 24–72h'],
                ['🔒', 'Secure Shopping',  '100% genuine & verified products'],
                ['💬', 'WhatsApp Support', 'Chat with us 7 days a week'],
                ['✅', 'Easy Returns',     '7-day return policy'],
            ];
            foreach ($perks as [$icon, $title, $desc]): ?>
            <div class="trust-card">
                <div class="trust-card__icon"><?= $icon ?></div>
                <div class="trust-card__title"><?= $title ?></div>
                <div class="trust-card__desc"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
