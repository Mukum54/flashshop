<?php
/**
 * cart.php — Shopping Cart + WhatsApp Checkout
 * FlashShop Electronics
 *
 * Flow: Add/Update/Remove items → "Order via WhatsApp"
 *       → saves order to DB → opens wa.me link with full order summary
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ── Handle POST actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    requireLogin();

    $action = cleanInput($_POST['action'] ?? '', 20);

    if ($action === 'add') {
        $productId = cleanInt($_POST['product_id'] ?? 0, 1);
        $qty       = cleanInt($_POST['quantity'] ?? 1, 1, 99);

        if ($productId) {
            addToCart($pdo, (int)$_SESSION['user_id'], $productId, $qty);
            if (empty($_SESSION['flash'])) {
                setFlash('success', '✓ Item added to cart!');
            }
        }
        $ref = $_SERVER['HTTP_REFERER'] ?? '/shop.php';
        // Prevent open redirect
        $ref = preg_match('/^https?:\/\/[^\/]*\//', $ref) ? preg_replace('/^https?:\/\/[^\/]*/', '', $ref) : '/shop.php';
        header('Location: ' . $ref);
        exit;

    } elseif ($action === 'update') {
        $cartId = cleanInt($_POST['cart_id'] ?? 0, 1);
        $qty    = cleanInt($_POST['quantity'] ?? 1, 1, 99);
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")
            ->execute([$qty, $cartId, $_SESSION['user_id']]);
        setFlash('success', 'Cart updated.');
        header('Location: /cart.php'); exit;

    } elseif ($action === 'remove') {
        $cartId = cleanInt($_POST['cart_id'] ?? 0, 1);
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")
            ->execute([$cartId, $_SESSION['user_id']]);
        setFlash('success', 'Item removed.');
        header('Location: /cart.php'); exit;

    } elseif ($action === 'whatsapp_checkout') {
        // ── WhatsApp Checkout ──────────────────────────────
        if (!isLoggedIn()) {
            header('Location: /login.php?redirect=/cart.php'); exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.id AS cart_id, c.quantity, p.id AS product_id,
                   p.name, p.price, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND p.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            setFlash('error', 'Your cart is empty.');
            header('Location: /cart.php'); exit;
        }

        // Calculate total
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

        // Save order in DB for admin tracking
        $txRef = generateTxRef();
        $pdo->prepare("INSERT INTO orders (user_id, total_amount, whatsapp_sent, status, transaction_ref) VALUES (?, ?, 1, 'pending', ?)")
            ->execute([$_SESSION['user_id'], $total, $txRef]);
        $orderId = (int) $pdo->lastInsertId();

        // Save order items
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $itemStmt->execute([$orderId, $item['product_id'], $item['name'], $item['quantity'], $item['price']]);
        }

        // Clear cart
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);

        // Store for success page
        $_SESSION['last_order_id'] = $orderId;
        $_SESSION['last_tx_ref']   = $txRef;
        $_SESSION['last_total']    = $total;
        $_SESSION['last_items']    = array_map(fn($i) => ['name' => $i['name'], 'quantity' => $i['quantity'], 'price' => $i['price']], $items);

        // Build WhatsApp URL and redirect
        $waUrl = buildWhatsAppUrl($items, $total, $txRef);
        header('Location: /order-success.php?wa=' . urlencode($waUrl));
        exit;
    }
}

// ── Fetch cart items ──────────────────────────────────────
$cartItems = [];
$cartTotal = 0;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity, p.id AS product_id,
               p.name, p.price, p.image_url, p.stock, cat.name AS category_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN category cat ON p.category_id = cat.id
        WHERE c.user_id = ? AND p.is_active = 1
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) {
        $cartTotal += $item['price'] * $item['quantity'];
    }
}

$pageTitle = 'Shopping Cart';
include __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Shopping Cart</h1>
        <p><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart</p>
    </div>
</div>

<div class="container">

<?php if (!isLoggedIn()): ?>
<div class="empty-state" style="padding:80px 0;">
    <div class="empty-state__icon">🛒</div>
    <h2>Sign in to view your cart</h2>
    <p>Your cart items are saved to your account.</p>
    <a href="/login.php?redirect=/cart.php" class="btn btn--primary">Sign In</a>
</div>

<?php elseif (empty($cartItems)): ?>
<div class="empty-state" style="padding:80px 0;">
    <div class="empty-state__icon">🛒</div>
    <h2>Your cart is empty</h2>
    <p>Add some awesome electronics and come back!</p>
    <a href="/shop.php" class="btn btn--primary">Browse Products</a>
</div>

<?php else: ?>
<div class="cart-layout">

    <!-- Cart Items -->
    <div>
        <div class="cart-box">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td>
                        <div class="cart-product">
                            <img src="<?= e($item['image_url'] ?: '') ?>"
                                 alt="<?= e($item['name']) ?>"
                                 class="cart-product__img">
                            <div>
                                <div class="cart-product__name">
                                    <a href="/product.php?id=<?= $item['product_id'] ?>"><?= e($item['name']) ?></a>
                                </div>
                                <div class="cart-product__cat"><?= e($item['category_name']) ?></div>
                                <?php if ($item['stock'] < 3): ?>
                                <div style="font-size:0.75rem;color:var(--warning);margin-top:4px;">⚠️ Only <?= $item['stock'] ?> left!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= formatPrice($item['price']) ?></td>
                    <td>
                        <form action="/cart.php" method="POST" class="cart-qty">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                            <input type="hidden" name="action"   value="update">
                            <input type="hidden" name="cart_id"  value="<?= $item['cart_id'] ?>">
                            <input type="number"  name="quantity" value="<?= $item['quantity'] ?>"
                                   min="1" max="<?= min($item['stock'], 99) ?>"
                                   onchange="this.form.submit()"
                                   aria-label="Quantity for <?= e($item['name']) ?>">
                        </form>
                    </td>
                    <td><strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong></td>
                    <td>
                        <form action="/cart.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                            <input type="hidden" name="action"   value="remove">
                            <input type="hidden" name="cart_id"  value="<?= $item['cart_id'] ?>">
                            <button type="submit" class="cart-remove"
                                    data-confirm="Remove <?= e($item['name']) ?>?">✕ Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            <a href="/shop.php" class="btn btn--ghost btn--sm">← Continue Shopping</a>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="cart-summary">
        <div class="cart-box">
            <h3>Order Summary</h3>

            <div class="cart-summary-row">
                <span>Subtotal (<?= count($cartItems) ?> items)</span>
                <span><?= formatPrice($cartTotal) ?></span>
            </div>
            <div class="cart-summary-row">
                <span>Delivery</span>
                <span style="color:var(--success);">Discussed on WhatsApp</span>
            </div>
            <div class="cart-summary-total">
                <span>Total</span>
                <span><?= formatPrice($cartTotal) ?></span>
            </div>

            <div class="cart-whatsapp-note">
                💬 <div>Clicking <strong>Order via WhatsApp</strong> will send your complete order to our team for confirmation and delivery arrangement.</div>
            </div>

            <form action="/cart.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                <input type="hidden" name="action"     value="whatsapp_checkout">
                <button type="submit" class="btn btn--whatsapp btn--full btn--lg">
                    💬 Order via WhatsApp
                </button>
            </form>

            <div style="text-align:center;margin-top:14px;font-size:0.78rem;color:var(--muted);">
                🔒 Your information is safe & secure
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
