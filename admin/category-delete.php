<?php
/**
 * admin/category-delete.php — Delete Category (POST-only)
 * Deactivates products in category instead of hard-deleting them.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/categories.php'); exit;
}
validateCsrf();

$catId = cleanInt($_POST['category_id'] ?? 0, 1);
$stmt  = $pdo->prepare("SELECT name FROM category WHERE id = ?");
$stmt->execute([$catId]);
$cat   = $stmt->fetch();

if ($cat) {
    // Deactivate products instead of hard-delete (keeps order history intact)
    $pdo->prepare("UPDATE products SET is_active = 0 WHERE category_id = ?")->execute([$catId]);
    $pdo->prepare("DELETE FROM category WHERE id = ?")->execute([$catId]);
    setFlash('success', '✓ Category "' . $cat['name'] . '" deleted. Its products are now hidden.');
} else {
    setFlash('error', 'Category not found.');
}

header('Location: /admin/categories.php');
exit;
