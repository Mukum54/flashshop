<?php
/**
 * admin/index.php — Admin Dashboard
 * FlashShop Electronics Admin Panel
 *
 * Shows: stat cards + recent orders
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// ── Stats ────────────────────────────────────────────────
$totalUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='completed'")->fetchColumn();

// ── Recent 10 orders ────────────────────────────────────
$recentOrders = $pdo->query("
    SELECT o.*, u.name AS user_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll();

// ── Low stock alert ─────────────────────────────────────
$lowStock = $pdo->query("
    SELECT id, name, stock FROM products
    WHERE stock > 0 AND stock <= 5 AND is_active = 1
    ORDER BY stock ASC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | FlashShop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
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
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
            <div class="admin-topbar__title">Dashboard</div>
        </div>
        <div class="admin-topbar__actions">
            <a href="/index.php" class="admin-view-site" target="_blank">↗ View Store</a>
            <a href="/logout.php" class="admin-logout">↪ Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <?php renderFlash(); ?>

        <div class="admin-page-header">
            <h1>Welcome back, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?> 👋</h1>
            <p class="admin-breadcrumb">Here's what's happening in your store today.</p>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--blue">👤</div>
                <div>
                    <div class="stat-card__val"><?= number_format($totalUsers) ?></div>
                    <div class="stat-card__lbl">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--green">📦</div>
                <div>
                    <div class="stat-card__val"><?= number_format($totalProducts) ?></div>
                    <div class="stat-card__lbl">Products</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--amber">🛒</div>
                <div>
                    <div class="stat-card__val"><?= number_format($totalOrders) ?></div>
                    <div class="stat-card__lbl">WhatsApp Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon stat-card__icon--purple">💰</div>
                <div>
                    <div class="stat-card__val"><?= formatPrice($totalRevenue) ?></div>
                    <div class="stat-card__lbl">Confirmed Revenue</div>
                </div>
            </div>
        </div>

        <div class="admin-two-col">

            <!-- Recent Orders -->
            <div class="admin-card" style="grid-column: 1 / -1;">
                <div class="admin-card__header">
                    <h3>📋 Recent Orders</h3>
                    <a href="/admin/orders.php" class="action-link action-link--view">View All →</a>
                </div>
                <?php if (empty($recentOrders)): ?>
                <p style="padding:20px;color:var(--muted);font-size:0.9rem;">No orders yet. Share your store link to get started!</p>
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
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong><br>
                                <code style="font-size:0.7rem;color:var(--muted);"><?= e(substr($order['transaction_ref'], 0, 16)) ?>…</code>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:0.9rem;"><?= e($order['user_name']) ?></div>
                                <div style="font-size:0.8rem;color:var(--muted);"><?= e($order['email']) ?></div>
                            </td>
                            <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                            <td><span class="status-badge status-badge--<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td style="font-size:0.85rem;color:var(--muted);"><?= date('M j, Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <a href="/admin/orders.php?id=<?= $order['id'] ?>" class="action-link action-link--view">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Low Stock Alert -->
            <?php if (!empty($lowStock)): ?>
            <div class="admin-card">
                <div class="admin-card__header">
                    <h3>⚠️ Low Stock Alert</h3>
                    <a href="/admin/products.php" class="action-link action-link--view">Manage →</a>
                </div>
                <div class="filter-list">
                    <?php foreach ($lowStock as $p): ?>
                    <div class="filter-item" style="cursor:default;">
                        <span><?= e($p['name']) ?></span>
                        <span class="badge badge--warning"><?= $p['stock'] ?> left</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.admin-two-col -->

    </div><!-- /.admin-content -->
</div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->
<script src="/assets/js/main.js"></script>
</body>
</html>
