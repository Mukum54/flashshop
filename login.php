<?php
/**
 * login.php — Sign In Page
 * FlashShop E-Commerce (Production Build)
 *
 * Security features:
 *  - CSRF token validation
 *  - Rate limiting (10 attempts per 5 minutes per IP)
 *  - bcrypt password verification
 *  - Session fixation protection
 *  - Input sanitisation
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Already logged in?
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    // Rate limit: max 10 login attempts per 5 minutes
    if (isRateLimited($pdo, 'login', 10, 300)) {
        $errors[] = 'Too many login attempts. Please wait 5 minutes and try again.';
    } else {
        $email    = cleanEmail($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email)           $errors[] = 'Please enter a valid email address.';
        if (!$password)        $errors[] = 'Password is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, is_banned FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                // Generic error — don't reveal which field is wrong
                $errors[] = 'Invalid email or password.';
            } elseif ($user['is_banned']) {
                $errors[] = 'This account has been suspended. Contact support.';
            } else {
                // Success — regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                $redirect = cleanInput($_GET['redirect'] ?? '/index.php', 200);
                // Safety: only redirect to local paths
                if (!preg_match('/^\/[a-zA-Z0-9\/?=&_\-\.]*$/', $redirect)) {
                    $redirect = '/index.php';
                }

                setFlash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '! 👋');
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

$pageTitle = 'Sign In';
include __DIR__ . '/includes/header.php';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-card__header">
            <div class="form-card__icon">🔐</div>
            <h1>Welcome Back</h1>
            <p>Sign in to your FlashShop account</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="flash flash--error" style="border-radius:var(--radius-md); margin-bottom:24px;" role="alert">
            <div><?= implode('<br>', array_map('e', $errors)) ?></div>
        </div>
        <?php endif; ?>

        <form action="/login.php" method="POST" novalidate autocomplete="on" id="login-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input
                    type="email" id="email" name="email" class="form-control"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="you@example.com"
                    autocomplete="email" required
                    maxlength="150"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input
                        type="password" id="password" name="password" class="form-control"
                        placeholder="••••••••"
                        autocomplete="current-password" required
                        maxlength="128"
                        style="padding-right:48px;"
                    >
                    <button type="button" id="pw-toggle" aria-label="Show password"
                        style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:1.1rem;">
                        👁
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:8px;">
                Sign In →
            </button>
        </form>

        <p class="form-footer">
            Don't have an account? <a href="/register.php">Create one free</a>
        </p>
    </div>
</div>

<script>
// Password toggle
document.getElementById('pw-toggle').addEventListener('click', function() {
    var pw = document.getElementById('password');
    var isText = pw.type === 'text';
    pw.type = isText ? 'password' : 'text';
    this.textContent = isText ? '👁' : '🙈';
    this.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
