<?php
/**
 * logout.php — Secure Session Termination
 * FlashShop E-Commerce
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: /index.php');
exit;
