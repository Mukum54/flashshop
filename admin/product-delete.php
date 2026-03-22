<?php
/**
 * admin/product-delete.php — Delete Product
 * POST-only, CSRF-protected, admin-only
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/products.php'); exit;
}

validateCsrf();

$productId = cleanInt($_POST['product_id'] ?? 0, 1);

// Verify product exists
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if ($product) {
    // Remove from carts first to avoid orphans
    $pdo->prepare("DELETE FROM cart WHERE product_id = ?")->execute([$productId]);
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
    setFlash('success', '✓ Product "' . $product['name'] . '" deleted.');
} else {
    setFlash('error', 'Product not found.');
}

header('Location: /admin/products.php');
exit;
