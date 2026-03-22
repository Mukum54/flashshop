<?php
/**
 * functions.php — Core Helper Functions
 * FlashShop E-Commerce (Production Build)
 *
 * Includes: Auth guards, CSRF, XSS escaping, flash messages,
 *           rate limiting, input sanitisation, cart helpers,
 *           price & WhatsApp formatting.
 *
 * Must be included AFTER config.php.
 */

// ════════════════════════════════════════════════════════════
// AUTH HELPERS
// ════════════════════════════════════════════════════════════

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        setFlash('error', 'Please sign in to continue.');
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    if (empty($_SESSION['user_id'])) {
        setFlash('error', 'Authentication required.');
        header('Location: /login.php');
        exit;
    }
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        setFlash('error', 'Access denied — admins only.');
        header('Location: /index.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

// ════════════════════════════════════════════════════════════
// FLASH MESSAGES
// ════════════════════════════════════════════════════════════

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function renderFlash(): void {
    if (!empty($_SESSION['flash'])) {
        $f    = $_SESSION['flash'];
        $type = htmlspecialchars($f['type'], ENT_QUOTES, 'UTF-8');
        $msg  = htmlspecialchars($f['msg'],  ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <div class="flash flash--{$type}" id="flash-banner" role="alert" aria-live="assertive">
            <span>{$msg}</span>
            <button onclick="this.parentElement.remove()" class="flash__close" aria-label="Dismiss">✕</button>
        </div>
        HTML;
        unset($_SESSION['flash']);
    }
}

// ════════════════════════════════════════════════════════════
// CSRF PROTECTION
// ════════════════════════════════════════════════════════════

function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token'])
        || !is_string($token)
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        die('Security check failed. Please go back and try again.');
    }
    // Rotate token after each use
    unset($_SESSION['csrf_token']);
}

// ════════════════════════════════════════════════════════════
// RATE LIMITING  (brute-force & spam protection)
// ════════════════════════════════════════════════════════════

/**
 * Check / increment rate limit for a given action.
 * Returns true if the limit has been exceeded.
 *
 * @param PDO    $pdo
 * @param string $action       e.g. 'login', 'register'
 * @param int    $maxAttempts  max allowed within $windowSeconds
 * @param int    $windowSeconds time window in seconds
 */
function isRateLimited(PDO $pdo, string $action, int $maxAttempts = 10, int $windowSeconds = 300): bool {
    $ip = getUserIP();

    // Clean old records first
    $pdo->prepare("DELETE FROM rate_limit WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)")
        ->execute([$windowSeconds]);

    // Check current count
    $stmt = $pdo->prepare("SELECT attempts FROM rate_limit WHERE ip_address = ? AND action = ?");
    $stmt->execute([$ip, $action]);
    $row = $stmt->fetch();

    if ($row && $row['attempts'] >= $maxAttempts) {
        return true;          // Blocked
    }

    // Upsert attempt
    $pdo->prepare("
        INSERT INTO rate_limit (ip_address, action, attempts)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()
    ")->execute([$ip, $action]);

    return false;
}

function getUserIP(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = filter_var($_SERVER[$key], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($ip) return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ════════════════════════════════════════════════════════════
// INPUT SANITISATION
// ════════════════════════════════════════════════════════════

/** HTML-escape a string for safe output. */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Trim and sanitise a general text input. */
function cleanInput(string $value, int $maxLen = 500): string {
    $value = trim($value);
    $value = strip_tags($value);
    return mb_substr($value, 0, $maxLen, 'UTF-8');
}

/** Sanitise and validate an email address. */
function cleanEmail(string $email): string|false {
    $email = trim(strtolower($email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/** Ensure an integer is within optional bounds. */
function cleanInt(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int {
    $value = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    return max($min, min($max, $value));
}

// ════════════════════════════════════════════════════════════
// FORMATTING HELPERS
// ════════════════════════════════════════════════════════════

/** Format a number as FCFA price. e.g. 45000 → "45 000 FCFA" */
function formatPrice(float $amount): string {
    return number_format($amount, 0, '.', ' ') . ' FCFA';
}

function generateTxRef(): string {
    return 'FS-' . strtoupper(bin2hex(random_bytes(6))) . '-' . date('YmdHis');
}

// ════════════════════════════════════════════════════════════
// WHATSAPP CHECKOUT
// ════════════════════════════════════════════════════════════

/**
 * Build a WhatsApp wa.me URL with a pre-filled order message.
 *
 * @param array  $cartItems   Each item must have: name, quantity, price
 * @param float  $total       Grand total
 * @param string $txRef       Transaction reference
 * @return string             Full WhatsApp URL
 */
function buildWhatsAppUrl(array $cartItems, float $total, string $txRef): string {
    $waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '237654492653';

    $lines   = ["🛒 *New Order — FlashShop*", "📋 Ref: {$txRef}", ""];
    foreach ($cartItems as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $lines[]  = "• {$item['name']} × {$item['quantity']} — " . formatPrice($subtotal);
    }
    $lines[] = "";
    $lines[] = "💰 *Total: " . formatPrice($total) . "*";
    $lines[] = "";
    $lines[] = "Please confirm my order. Thank you! 🙏";

    $message = implode("\n", $lines);
    return 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($message);
}

// ════════════════════════════════════════════════════════════
// CART HELPERS
// ════════════════════════════════════════════════════════════

function getCartCount(PDO $pdo): int {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function addToCart(PDO $pdo, int $userId, int $productId, int $qty = 1): void {
    // Check product exists and has stock
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || $product['stock'] < 1) {
        setFlash('error', 'This product is out of stock.');
        return;
    }

    // Upsert: insert or increment quantity
    $pdo->prepare("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ")->execute([$userId, $productId, $qty, $qty]);
}

// ════════════════════════════════════════════════════════════
// PAGINATION
// ════════════════════════════════════════════════════════════

function paginate(int $page, int $perPage = 9): array {
    $page   = max(1, $page);
    $offset = ($page - 1) * $perPage;
    return ['limit' => $perPage, 'offset' => $offset];
}

function renderPagination(int $currentPage, int $totalItems, int $perPage, string $baseUrl): void {
    $totalPages = (int) ceil($totalItems / $perPage);
    if ($totalPages <= 1) return;

    $baseUrl = preg_replace('/([?&])page=\d+/', '', $baseUrl);
    $sep     = str_contains($baseUrl, '?') ? '&' : '?';

    echo '<nav class="pagination" aria-label="Page navigation">';
    if ($currentPage > 1) {
        echo "<a href=\"{$baseUrl}{$sep}page=" . ($currentPage - 1) . "\" class=\"pagination__item\" aria-label=\"Previous\">‹</a>";
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i === $currentPage) ? ' pagination__item--active' : '';
        $aria   = ($i === $currentPage) ? ' aria-current="page"' : '';
        echo "<a href=\"{$baseUrl}{$sep}page={$i}\" class=\"pagination__item{$active}\"{$aria}>{$i}</a>";
    }
    if ($currentPage < $totalPages) {
        echo "<a href=\"{$baseUrl}{$sep}page=" . ($currentPage + 1) . "\" class=\"pagination__item\" aria-label=\"Next\">›</a>";
    }
    echo '</nav>';
}

// ════════════════════════════════════════════════════════════
// SLUG GENERATOR
// ════════════════════════════════════════════════════════════

function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}
