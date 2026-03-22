<?php
/**
 * order-success.php — Order Confirmation + WhatsApp Redirect
 * FlashShop Electronics
 *
 * Shown after user confirms WhatsApp checkout.
 * Opens WhatsApp in a new tab, then shows a confirmation receipt.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$orderId = $_SESSION['last_order_id'] ?? null;
$txRef   = $_SESSION['last_tx_ref']   ?? '—';
$total   = $_SESSION['last_total']    ?? 0;
$items   = $_SESSION['last_items']    ?? [];
$waUrl   = cleanInput($_GET['wa'] ?? '', 2000);

if (!$orderId) {
    header('Location: /index.php');
    exit;
}

// Verify the order actually belongs to this user
$stmt = $pdo->prepare("SELECT id, total_amount, created_at, transaction_ref FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: /index.php');
    exit;
}

// Fetch the actual order items from DB (fallback if session items empty)
if (empty($items)) {
    $iStmt = $pdo->prepare("SELECT product_name AS name, quantity, unit_price AS price FROM order_items WHERE order_id = ?");
    $iStmt->execute([$orderId]);
    $items = $iStmt->fetchAll();
}

// Clear session order data so page can't be replayed
unset($_SESSION['last_order_id'], $_SESSION['last_tx_ref'], $_SESSION['last_total'], $_SESSION['last_items']);

$pageTitle = 'Order Confirmed';
include __DIR__ . '/includes/header.php';
?>

<div class="success-page">
    <div class="success-card">
        <!-- Animated checkmark -->
        <div class="success-icon">✓</div>

        <h1>Order Sent!</h1>
        <p style="font-size:1.05rem; color:var(--charcoal); margin-bottom:6px;">
            Your order has been submitted via WhatsApp.
        </p>
        <p style="color:var(--muted); margin-bottom:6px;">
            Our team will contact you shortly to confirm delivery details.
        </p>
        <p class="order-ref">
            Reference: <strong><?= e($order['transaction_ref']) ?></strong>
        </p>

        <!-- Order Items -->
        <?php if (!empty($items)): ?>
        <div class="success-items">
            <?php foreach ($items as $item): ?>
            <div class="success-item">
                <span><?= e($item['name']) ?> × <?= (int)$item['quantity'] ?></span>
                <strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
            </div>
            <?php endforeach; ?>
            <div class="success-total">
                <span>Total</span>
                <span><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <p style="font-size:0.82rem; color:var(--muted); margin-bottom:28px;">
            Order date: <strong><?= date('F j, Y \a\t H:i', strtotime($order['created_at'])) ?></strong>
        </p>

        <!-- WhatsApp re-open button (in case tab was blocked) -->
        <?php if ($waUrl): ?>
        <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener" class="btn btn--whatsapp btn--lg btn--full" style="margin-bottom:14px;">
            💬 Open WhatsApp Order
        </a>
        <p style="font-size:0.78rem; color:var(--muted); margin-bottom:24px;">
            If WhatsApp didn't open automatically, click the button above.
        </p>
        <?php endif; ?>

        <a href="/shop.php" class="btn btn--secondary">← Continue Shopping</a>
    </div>
</div>

<?php if ($waUrl): ?>
<script>
// Auto-open WhatsApp (popup policy may block this, button above is the fallback)
window.addEventListener('load', function() {
    setTimeout(function() {
        window.open(<?= json_encode($waUrl) ?>, '_blank', 'noopener,noreferrer');
    }, 800);
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
