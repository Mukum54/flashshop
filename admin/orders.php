<?php
/**
 * admin/orders.php — View & Manage WhatsApp Orders
 * FlashShop Admin
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

$statusFilter = cleanInput($_GET['status'] ?? '', 20);
$page         = cleanInt($_GET['page'] ?? 1, 1);
$perPage      = 20;
$pg           = paginate($page, $perPage);

$where  = $statusFilter ? "WHERE o.status = ?" : '';
$params = $statusFilter ? [$statusFilter] : [];

$total = (int) $pdo->prepare("SELECT COUNT(*) FROM orders o $where")->execute($params) ? null : 0;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$orderStmt = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$orderStmt->execute(array_merge($params, [$pg['limit'], $pg['offset']]));
$orders = $orderStmt->fetchAll();

// Handle single-order detail view
$viewId    = cleanInt($_GET['id'] ?? 0, 1);
$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $s = $pdo->prepare("SELECT o.*, u.name AS user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $s->execute([$viewId]);
    $viewOrder = $s->fetch();
    if ($viewOrder) {
        $i = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $i->execute([$viewId]);
        $viewItems = $i->fetchAll();
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    validateCsrf();
    $ordId  = cleanInt($_POST['order_id'] ?? 0, 1);
    $status = cleanInput($_POST['status'] ?? '', 20);
    $allowed = ['pending','confirmed','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $ordId]);
        setFlash('success', "Order #$ordId status updated to $status.");
    }
    header('Location: /admin/orders.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Orders | FlashShop Admin</title>
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
        <div class="admin-topbar__title"><?= $viewOrder ? 'Order #' . $viewId : 'Orders' ?></div>
    </div>
    <div class="admin-topbar__actions">
        <?php if ($viewOrder): ?>
        <a href="/admin/orders.php" class="admin-view-site">← Back to Orders</a>
        <?php endif; ?>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>

<?php if ($viewOrder): ?>
<!-- ── Single Order View ── -->
<div class="admin-page-header">
    <h1>Order #<?= $viewOrder['id'] ?></h1>
    <p class="admin-breadcrumb">Reference: <?= e($viewOrder['transaction_ref']) ?></p>
</div>
<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
    <div class="admin-card">
        <div class="admin-card__header"><h3>Items Ordered</h3></div>
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($viewItems as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= formatPrice($item['unit_price']) ?></td>
                <td><strong><?= formatPrice($item['unit_price'] * $item['quantity']) ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="padding:16px 20px; border-top:1px solid var(--border);font-size:1.05rem;font-weight:700;color:var(--amber);">
            Total: <?= formatPrice($viewOrder['total_amount']) ?>
        </div>
    </div>
    <div>
        <div class="admin-card">
            <div class="admin-card__header"><h3>Customer</h3></div>
            <div class="admin-card__body">
                <p style="margin-bottom:6px;"><strong><?= e($viewOrder['user_name']) ?></strong></p>
                <p style="font-size:0.85rem;color:var(--muted);margin-bottom:16px;"><?= e($viewOrder['email']) ?></p>
                <p style="font-size:0.85rem;color:var(--muted);">Ordered: <?= date('F j, Y H:i', strtotime($viewOrder['created_at'])) ?></p>
                <p style="font-size:0.85rem;color:var(--muted);margin-top:4px;">WhatsApp sent: <?= $viewOrder['whatsapp_sent'] ? '✓ Yes' : '✗ No' ?></p>
            </div>
        </div>
        <div class="admin-card" style="margin-top:16px;">
            <div class="admin-card__header"><h3>Update Status</h3></div>
            <div class="admin-card__body">
                <span class="status-badge status-badge--<?= e($viewOrder['status']) ?>" style="margin-bottom:14px;display:inline-block;">
                    <?= e(ucfirst($viewOrder['status'])) ?>
                </span>
                <form method="POST" action="/admin/orders.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                    <select name="status" class="form-control" style="margin-bottom:10px;">
                        <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn--primary btn--sm btn--full">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Orders List ── -->
<div class="admin-page-header">
    <h1>WhatsApp Orders</h1>
    <p class="admin-breadcrumb"><?= number_format($total) ?> order<?= $total !== 1 ? 's' : '' ?> total</p>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
    <?php foreach (['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $lbl): ?>
    <a href="/admin/orders.php?status=<?= $val ?>"
       class="btn btn--sm <?= $statusFilter === $val ? 'btn--primary' : 'btn--ghost' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <?php if (empty($orders)): ?>
    <p style="padding:40px;text-align:center;color:var(--muted);">No orders found.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    <strong>#<?= $order['id'] ?></strong><br>
                    <code style="font-size:0.7rem;color:var(--muted);"><?= e(substr($order['transaction_ref'], 0, 14)) ?>…</code>
                </td>
                <td>
                    <div style="font-weight:600;"><?= e($order['user_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--muted);"><?= e($order['email']) ?></div>
                </td>
                <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                <td><span class="status-badge status-badge--<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                <td style="font-size:0.82rem;color:var(--muted);"><?= date('M j, Y H:i', strtotime($order['created_at'])) ?></td>
                <td><a href="/admin/orders.php?id=<?= $order['id'] ?>" class="action-link action-link--view">View →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$baseUrl = '/admin/orders.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '');
renderPagination($page, $total, $perPage, $baseUrl);
?>
<?php endif; ?>

</div></div></div>
<script src="/assets/js/main.js"></script>
</body>
</html>
