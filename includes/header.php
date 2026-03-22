<?php
/**
 * includes/header.php — Site Header
 * FlashShop E-Commerce (Electronics Store)
 */
$cartCount = isLoggedIn() ? getCartCount($pdo) : 0;
$pageTitle = ($pageTitle ?? 'FlashShop') . ' | FlashShop — Electronics';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FlashShop — Premium electronics store. Smartphones, laptops, audio and accessories. Order via WhatsApp.">
    <title><?= e($pageTitle) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body>

<!-- ── Site Header ──────────────────────────────────────────── -->
<header class="site-header" id="site-header">
    <div class="container">
        <div class="header__inner">

            <!-- Logo -->
            <a href="/index.php" class="header__logo" aria-label="FlashShop Home">
                <span class="logo__icon">⚡</span>
                <span class="logo__text">Flash<span class="logo__accent">Shop</span></span>
            </a>

            <!-- Main Nav -->
            <nav class="header__nav" id="main-nav" aria-label="Main navigation">
                <a href="/index.php" class="nav__link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'nav__link--active' : '' ?>">Home</a>

                <!-- Categories dropdown -->
                <div class="nav__dropdown">
                    <button class="nav__link" aria-haspopup="true" aria-expanded="false">
                        Shop <span class="dropdown__arrow">▾</span>
                    </button>
                    <div class="nav__dropdown-menu" role="menu">
                        <a href="/shop.php" class="dropdown__item" role="menuitem">🛍️ All Products</a>
                        <a href="/shop.php?category=1" class="dropdown__item" role="menuitem">📱 Smartphones</a>
                        <a href="/shop.php?category=2" class="dropdown__item" role="menuitem">💻 Laptops</a>
                        <a href="/shop.php?category=3" class="dropdown__item" role="menuitem">🎧 Audio</a>
                        <a href="/shop.php?category=4" class="dropdown__item" role="menuitem">🔌 Accessories</a>
                        <a href="/shop.php?category=5" class="dropdown__item" role="menuitem">🎮 Gaming</a>
                        <a href="/shop.php?category=6" class="dropdown__item" role="menuitem">🏠 Smart Home</a>
                    </div>
                </div>

                <a href="/shop.php?featured=1" class="nav__link">Deals</a>
            </nav>

            <!-- Actions -->
            <div class="header__actions">
                <!-- Search toggle -->
                <button class="action-btn" id="search-toggle" aria-label="Search" aria-expanded="false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>

                <!-- Cart -->
                <a href="/cart.php" class="action-btn" aria-label="Shopping cart (<?= $cartCount ?> items)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge" aria-hidden="true"><?= $cartCount > 9 ? '9+' : $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                <!-- User menu -->
                <div class="user-menu" aria-label="User account">
                    <button class="user-menu__btn" aria-haspopup="true">
                        <div class="user-menu__avatar" aria-hidden="true">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="user-menu__name"><?= e(explode(' ', $_SESSION['user_name'])[0]) ?></span>
                        <span aria-hidden="true">▾</span>
                    </button>
                    <div class="user-menu__dropdown" role="menu">
                        <?php if (isAdmin()): ?>
                        <a href="/admin/index.php" class="user-menu__item user-menu__item--admin" role="menuitem">⚙️ Admin Panel</a>
                        <div class="user-menu__divider" role="separator"></div>
                        <?php endif; ?>
                        <a href="/cart.php" class="user-menu__item" role="menuitem">🛒 My Cart</a>
                        <div class="user-menu__divider" role="separator"></div>
                        <a href="/logout.php" class="user-menu__item user-menu__item--danger" role="menuitem">↪ Sign Out</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="/login.php" class="btn btn--primary btn--sm">Sign In</a>
                <?php endif; ?>

                <!-- Hamburger -->
                <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="search-bar" id="search-bar" aria-hidden="true">
        <div class="container">
            <form class="search-form" action="/shop.php" method="GET" role="search">
                <input class="search-form__input" type="search" name="q" placeholder="Search electronics..." autocomplete="off" aria-label="Search products">
                <button class="search-form__btn" type="submit" aria-label="Search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- Mobile nav overlay -->
<div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>

<!-- Flash message -->
<div class="flash-container"><?php renderFlash(); ?></div>

<main id="main-content">
