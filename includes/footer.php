<?php
/**
 * includes/footer.php — Site Footer
 * FlashShop E-Commerce (Electronics Store)
 */
?>
</main><!-- /#main-content -->

<footer class="site-footer">
    <div class="footer__top">
        <div class="container">
            <div class="footer__grid">
                <!-- Brand -->
                <div class="footer__brand">
                    <div class="footer__logo">⚡ Flash<span style="color:var(--amber)">Shop</span></div>
                    <p class="footer__tagline">
                        Your trusted source for premium electronics devices.<br>
                        Fast delivery · Expert advice · WhatsApp ordering.
                    </p>
                    <!-- WhatsApp CTA -->
                    <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="whatsapp-fab footer__whatsapp" aria-label="Chat on WhatsApp">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp Us
                    </a>
                </div>

                <!-- Links -->
                <div class="footer__links">
                    <div>
                        <h4 class="footer__heading">Shop</h4>
                        <a href="/shop.php?category=1" class="footer__link">📱 Smartphones</a>
                        <a href="/shop.php?category=2" class="footer__link">💻 Laptops</a>
                        <a href="/shop.php?category=3" class="footer__link">🎧 Audio</a>
                        <a href="/shop.php?category=4" class="footer__link">🔌 Accessories</a>
                        <a href="/shop.php?category=5" class="footer__link">🎮 Gaming</a>
                        <a href="/shop.php?category=6" class="footer__link">🏠 Smart Home</a>
                    </div>
                    <div>
                        <h4 class="footer__heading">Account</h4>
                        <a href="/login.php"    class="footer__link">Sign In</a>
                        <a href="/register.php" class="footer__link">Create Account</a>
                        <a href="/cart.php"     class="footer__link">My Cart</a>
                    </div>
                    <div>
                        <h4 class="footer__heading">Contact</h4>
                        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" class="footer__link">💬 WhatsApp Order</a>
                        <span class="footer__link">📞 +237 654 492 653</span>
                        <span class="footer__link">📍 Cameroon</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer__bottom">
        <div class="container">
            <div class="footer__bottom-inner">
                <p class="footer__copy">© <?= date('Y') ?> FlashShop Electronics. All rights reserved.</p>
                <div class="footer__trust">
                    <span>🔒 Secure</span>
                    <span>✅ Verified Products</span>
                    <span>⚡ Fast Delivery</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp button -->
<a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener noreferrer"
   class="whatsapp-fab" aria-label="Chat on WhatsApp" id="whatsapp-btn">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<script src="/assets/js/main.js"></script>
</body>
</html>
