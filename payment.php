<?php
/**
 * payment.php — Payment Form
 * Processes fake card details, inserts payment record, clears cart
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

requireLogin();

// Must have come through checkout
if (empty($_SESSION['checkout'])) {
    header('Location: /checkout.php'); exit;
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_url
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: /cart.php'); exit;
}

$grandTotal = $_SESSION['checkout']['grand_total'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    // Server-side card validation (basic)
    $cardNum = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry  = trim($_POST['expiry'] ?? '');
    $cvv     = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
    $method  = $_POST['method'] ?? 'card';

    $errors = [];
    if (strlen($cardNum) !== 16) $errors[] = 'Invalid card number.';
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) $errors[] = 'Invalid expiry date.';
    if (strlen($cvv) < 3) $errors[] = 'Invalid CVV.';

    if (empty($errors)) {
        $txRef = generateTxRef();

        // Insert payment record
        $payStmt = $pdo->prepare("
            INSERT INTO payment (user_id, total_amount, method, status, transaction_ref)
            VALUES (?, ?, ?, 'completed', ?)
        ");
        $payStmt->execute([$_SESSION['user_id'], $grandTotal, $method, $txRef]);
        $paymentId = $pdo->lastInsertId();

        // Save order items
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (payment_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($cartItems as $item) {
            $itemStmt->execute([$paymentId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Clear the user's cart
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")
            ->execute([$_SESSION['user_id']]);

        // Clear checkout session
        unset($_SESSION['checkout']);

        // Store order id in session for success page
        $_SESSION['last_order_id'] = $paymentId;
        $_SESSION['last_tx_ref']   = $txRef;

        header('Location: /order-success.php'); exit;
    }
}

$pageTitle = 'Payment';
include __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1>Payment</h1>
        <nav class="breadcrumb-trail">
            <a href="/cart.php">Cart</a>
            <span>/</span>
            <a href="/checkout.php">Shipping</a>
            <span>/</span>
            <span>Payment</span>
        </nav>
    </div>
</div>

<div class="container">
<div class="checkout-layout">

    <div class="checkout-form-box">
        <h2>💳 Payment Details</h2>

        <?php if (!empty($errors)): ?>
        <div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:20px;">
            <?= implode('<br>', array_map('e', $errors)) ?>
        </div>
        <?php endif; ?>

        <!-- Shipping recap -->
        <div style="background:var(--body-bg);border-radius:var(--radius-md);padding:16px;margin-bottom:28px;font-size:0.9rem;">
            <strong>Delivering to:</strong>
            <?= e($_SESSION['checkout']['full_name']) ?>,
            <?= e($_SESSION['checkout']['address']) ?>,
            <?= e($_SESSION['checkout']['city']) ?>,
            <?= e($_SESSION['checkout']['country']) ?>
            <a href="/checkout.php" style="margin-left:10px;font-size:0.8rem;color:var(--amber-dark);">Edit</a>
        </div>

        <form action="/payment.php" method="POST" id="payment-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

            <!-- Payment method selector -->
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <?php foreach (['card'=>'💳 Credit/Debit Card','paypal'=>'🅿️ PayPal','bank'=>'🏦 Bank Transfer'] as $val => $label): ?>
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="method" value="<?= $val ?>"
                               <?= $val === 'card' ? 'checked' : '' ?>
                               style="margin-right:6px;">
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" class="form-control"
                       placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="cc-number">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="expiry">Expiry Date</label>
                    <input type="text" id="expiry" name="expiry" class="form-control"
                           placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" class="form-control"
                           placeholder="123" maxlength="4" autocomplete="cc-csc">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="card_holder">Cardholder Name</label>
                <input type="text" id="card_holder" name="card_holder" class="form-control"
                       placeholder="<?= e($_SESSION['user_name'] ?? '') ?>"
                       value="<?= e($_SESSION['user_name'] ?? '') ?>">
            </div>

            <div style="background:rgba(232,160,32,.08);border:1px solid rgba(232,160,32,.3);border-radius:var(--radius-md);padding:14px;margin-bottom:20px;font-size:0.85rem;color:var(--amber-dark);">
                🔒 This is a demo payment. Enter any 16-digit number — no real charge will occur.
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg">
                Pay <?= formatPrice($grandTotal) ?> Now →
            </button>
        </form>
    </div>

    <!-- Order Summary -->
    <div class="order-summary-box">
        <h3>Order Summary</h3>
        <?php foreach ($cartItems as $item): ?>
        <div class="order-item">
            <img src="<?= e($item['image_url'] ?: '') ?>" alt="<?= e($item['name']) ?>" class="order-item__img">
            <span class="order-item__name"><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
            <span class="order-item__price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="order-grand-total" style="margin-top:16px;">
            <span>Total</span>
            <span><?= formatPrice($grandTotal) ?></span>
        </div>
    </div>

</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
