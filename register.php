<?php
/**
 * register.php — Customer Registration
 * FlashShop E-Commerce (Production Build)
 *
 * Security:
 *  - CSRF validation
 *  - Rate limiting (5 registrations per 10 min per IP)
 *  - Password strength enforcement
 *  - bcrypt(12) password hashing
 *  - Input sanitisation
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) { header('Location: /index.php'); exit; }

$errors = [];
$values = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    if (isRateLimited($pdo, 'register', 5, 600)) {
        $errors[] = 'Too many registration attempts. Please wait 10 minutes.';
    } else {
        $name     = cleanInput($_POST['name']     ?? '', 100);
        $email    = cleanEmail($_POST['email']    ?? '');
        $password = trim($_POST['password']       ?? '');
        $confirm  = trim($_POST['confirm']        ?? '');
        $values   = ['name' => $name, 'email' => $_POST['email'] ?? ''];

        // Validate
        if (strlen($name) < 2)              $errors[] = 'Full name must be at least 2 characters.';
        if (!$email)                         $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8)           $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';
        if ($password !== $confirm)          $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email address is already registered.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hash]);

            $userId = (int) $pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'customer';

            setFlash('success', 'Account created! Welcome to FlashShop, ' . explode(' ', $name)[0] . '! 🎉');
            header('Location: /index.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/includes/header.php';
?>

<div class="form-page">
    <div class="form-card">
        <div class="form-card__header">
            <div class="form-card__icon">👤</div>
            <h1>Create Account</h1>
            <p>Join FlashShop — free forever</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="flash flash--error" style="border-radius:var(--radius-md);margin-bottom:24px;" role="alert">
            <?= implode('<br>', array_map('e', $errors)) ?>
        </div>
        <?php endif; ?>

        <form action="/register.php" method="POST" novalidate id="register-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrf() ?>">

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="<?= e($values['name']) ?>"
                    placeholder="Jean-Pierre Kamdem"
                    autocomplete="name" required maxlength="100">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?= e($values['email']) ?>"
                    placeholder="you@example.com"
                    autocomplete="email" required maxlength="150">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Min. 8 chars, 1 uppercase, 1 number"
                    autocomplete="new-password" required maxlength="128">
                <div id="pw-strength" style="height:4px;border-radius:4px;margin-top:6px;background:var(--border);overflow:hidden;">
                    <div id="pw-strength-bar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:4px;"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" class="form-control"
                    placeholder="••••••••"
                    autocomplete="new-password" required maxlength="128">
            </div>

            <button type="submit" class="btn btn--primary btn--full btn--lg" style="margin-top:8px;">
                Create Account →
            </button>
        </form>

        <p class="form-footer">
            Already have an account? <a href="/login.php">Sign in</a>
        </p>
    </div>
</div>

<script>
// Password strength meter
(function() {
    var pw = document.getElementById('password');
    var bar = document.getElementById('pw-strength-bar');
    if (!pw || !bar) return;
    pw.addEventListener('input', function() {
        var v = pw.value, score = 0;
        if (v.length >= 8)             score++;
        if (/[A-Z]/.test(v))           score++;
        if (/[0-9]/.test(v))           score++;
        if (/[^A-Za-z0-9]/.test(v))    score++;
        var colors = ['#EF4444','#F59E0B','#3B82F6','#10B981'];
        bar.style.width = (score * 25) + '%';
        bar.style.background = colors[score - 1] || 'transparent';
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
