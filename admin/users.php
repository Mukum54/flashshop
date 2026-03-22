<?php
/**
 * admin/users.php — View All Users
 * FlashShop Admin (read-only + ban toggle)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

// Handle ban toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $userId = cleanInt($_POST['user_id'] ?? 0, 1);
    $action = cleanInput($_POST['action'] ?? '', 10);
    // Prevent banning self
    if ($userId && $userId !== (int)$_SESSION['user_id']) {
        if ($action === 'ban') {
            $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ?")->execute([$userId]);
            setFlash('warning', "User #$userId has been banned.");
        } elseif ($action === 'unban') {
            $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = ?")->execute([$userId]);
            setFlash('success', "User #$userId has been unbanned.");
        }
    }
    header('Location: /admin/users.php'); exit;
}

$page    = cleanInt($_GET['page'] ?? 1, 1);
$perPage = 20;
$pg      = paginate($page, $perPage);
$search  = cleanInput($_GET['q'] ?? '', 100);

$where  = $search ? "WHERE u.name LIKE ? OR u.email LIKE ?" : '';
$params = $search ? ["%$search%", "%$search%"] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_amount),0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$pg['limit'], $pg['offset']]));
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Users | FlashShop Admin</title>
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
        <div class="admin-topbar__title">Users</div>
    </div>
</div>
<div class="admin-content">
<?php renderFlash(); ?>

<div class="admin-page-header">
    <h1>Users</h1>
    <p class="admin-breadcrumb"><?= number_format($total) ?> registered users</p>
</div>

<!-- Search -->
<form action="/admin/users.php" method="GET" style="margin-bottom:20px;display:flex;gap:10px;">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name or email…"
           class="form-control" style="max-width:360px;">
    <button type="submit" class="btn btn--primary btn--sm">Search</button>
    <?php if ($search): ?>
    <a href="/admin/users.php" class="btn btn--ghost btn--sm">Clear</a>
    <?php endif; ?>
</form>

<div class="admin-card">
    <?php if (empty($users)): ?>
    <p style="padding:40px;text-align:center;color:var(--muted);">No users found.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
            <tr style="<?= $user['is_banned'] ? 'opacity:.55;' : '' ?>">
                <td><?= $user['id'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--amber);color:#000;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.8rem;flex-shrink:0;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <span><?= e($user['name']) ?></span>
                    </div>
                </td>
                <td style="font-size:0.85rem;color:var(--muted);"><?= e($user['email']) ?></td>
                <td>
                    <span class="badge <?= $user['role'] === 'admin' ? 'badge--amber' : 'badge--navy' ?>">
                        <?= e(ucfirst($user['role'])) ?>
                    </span>
                    <?php if ($user['is_banned']): ?>
                    <span class="badge badge--error" style="margin-left:4px;">Banned</span>
                    <?php endif; ?>
                </td>
                <td><?= $user['order_count'] ?></td>
                <td><?= formatPrice($user['total_spent']) ?></td>
                <td style="font-size:0.82rem;color:var(--muted);"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                <td>
                    <?php if ($user['id'] !== (int)$_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="action" value="<?= $user['is_banned'] ? 'unban' : 'ban' ?>">
                        <button type="submit" class="action-link <?= $user['is_banned'] ? 'action-link--edit' : 'action-link--delete' ?>"
                                data-confirm="<?= $user['is_banned'] ? 'Unban' : 'Ban' ?> this user?">
                            <?= $user['is_banned'] ? '✓ Unban' : '⊘ Ban' ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:0.8rem;color:var(--muted);">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$baseUrl = '/admin/users.php' . ($search ? '?q=' . urlencode($search) : '');
renderPagination($page, $total, $perPage, $baseUrl);
?>
</div></div></div>
<script src="/assets/js/main.js"></script>
</body>
</html>
