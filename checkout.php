<?php
/**
 * checkout.php — Shipping Information
 * Collects full name, address, city, country → stores in session → redirects to payment
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

requireLogin();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.quantity, p.id AS product_id, p.name, p.price, p.image_url
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty.');
    header('Location: /cart.php'); exit;
}

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + $item['price'] * $item['quantity'], 0);
$shipping  = $cartTotal >= 100 ? 0 : 9.99;
$tax       = $cartTotal * 0.08;
$grandTotal = $cartTotal + $shipping + $tax;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $country  = trim($_POST['country'] ?? '');

    if (empty($fullName) || empty($address) || empty($city) || empty($country)) {
        $error = 'Please fill in all shipping fields.';
    } else {
        $_SESSION['checkout'] = [
            'full_name'   => $fullName,
            'address'     => $address,
            'city'        => $city,
            'country'     => $country,
            'grand_total' => $grandTotal,
        ];
        header('Location: /payment.php'); exit;
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Checkout</h1>
        <nav class="breadcrumb-trail">
            <a href="/cart.php">Cart</a>
            <span>/</span>
            <span>Shipping</span>
            <span>/</span>
            <span style="color:rgba(255,255,255,.4)">Payment</span>
        </nav>
    </div>
</div>

<div class="container">
<div class="checkout-layout">

    <!-- Shipping Form -->
    <div class="checkout-form-box">
        <h2>Shipping Information</h2>

        <?php if (!empty($error)): ?>
        <div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:20px;"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="/checkout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

            <div class="form-group">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control"
                       placeholder="John Carter" value="<?= e($_SESSION['user_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="address">Street Address</label>
                <input type="text" id="address" name="address" class="form-control"
                       placeholder="123 Main Street, Apt 4B" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" id="city" name="city" class="form-control"
                           placeholder="New York" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="country">Country</label>
                    <select id="country" name="country" class="form-control" required>
                        <option value="">Select country…</option>
                        <option value="US">United States</option>
                        <option value="UK">United Kingdom</option>
                        <option value="CA">Canada</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="NG">Nigeria</option>
                        <option value="ZA">South Africa</option>
                        <option value="IN">India</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:12px;">
                Continue to Payment →
            </button>
        </form>
    </div>

    <!-- Order summary -->
    <div class="order-summary-box">
        <h3>Your Order</h3>
        <?php foreach ($cartItems as $item): ?>
        <div class="order-item">
            <img src="<?= e($item['image_url'] ?: '') ?>" alt="<?= e($item['name']) ?>" class="order-item__img">
            <span class="order-item__name"><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
            <span class="order-item__price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="order-total-row"><span>Subtotal</span><span><?= formatPrice($cartTotal) ?></span></div>
        <div class="order-total-row"><span>Shipping</span><span><?= $shipping > 0 ? formatPrice($shipping) : 'Free' ?></span></div>
        <div class="order-total-row"><span>Tax (8%)</span><span><?= formatPrice($tax) ?></span></div>
        <div class="order-grand-total"><span>Total</span><span><?= formatPrice($grandTotal) ?></span></div>
    </div>

</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
