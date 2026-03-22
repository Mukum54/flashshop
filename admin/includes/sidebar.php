<?php
/**
 * admin/includes/sidebar.php — Admin Sidebar Navigation
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function adminNavLink(string $href, string $icon, string $label, string $currentPage, array $matchPages = []): string {
    $base = basename($href);
    $active = ($base === $currentPage || in_array($currentPage, $matchPages)) ? 'sidebar__link--active' : '';
    return "<a href=\"{$href}\" class=\"sidebar__link {$active}\"><span class=\"sidebar__icon\">{$icon}</span>{$label}</a>";
}
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <a href="/admin/index.php" class="sidebar__logo">
        <span style="font-size:1.2rem;">⚡</span>
        <span class="sidebar__logo-text">Flash<span>Shop</span></span>
        <span class="sidebar__logo-badge">Admin</span>
    </a>

    <nav class="sidebar__nav">
        <div class="sidebar__section-label">Overview</div>
        <?= adminNavLink('/admin/index.php', '🏠', 'Dashboard', $currentPage) ?>

        <div class="sidebar__section-label">Catalog</div>
        <?= adminNavLink('/admin/products.php', '📦', 'Products', $currentPage, ['product-add.php','product-edit.php']) ?>
        <?= adminNavLink('/admin/categories.php', '🗂️', 'Categories', $currentPage, ['category-add.php','category-edit.php']) ?>

        <div class="sidebar__section-label">Sales</div>
        <?= adminNavLink('/admin/orders.php', '🛒', 'Orders', $currentPage) ?>

        <div class="sidebar__section-label">People</div>
        <?= adminNavLink('/admin/users.php', '👥', 'Users', $currentPage) ?>
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__user">
            <div class="sidebar__user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="sidebar__user-name"><?= e($_SESSION['user_name'] ?? 'Admin') ?></div>
                <div class="sidebar__user-role">Administrator</div>
            </div>
        </div>
        <a href="/logout.php" style="display:block;text-align:center;margin-top:10px;font-size:0.82rem;color:#9ca3af;text-decoration:none;">
            Sign out
        </a>
    </div>
</aside>
