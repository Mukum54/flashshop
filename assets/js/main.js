/**
 * main.js — FlashShop Frontend JavaScript
 * Handles: Nav toggle, search bar, dropdowns, confirm dialogs,
 *          flash auto-dismiss, admin sidebar toggle, qty steppers.
 */
(function () {
  'use strict';

  // ── Utility ───────────────────────────────────────────────
  function $(selector, ctx) { return (ctx || document).querySelector(selector); }
  function $$(selector, ctx) { return Array.from((ctx || document).querySelectorAll(selector)); }

  // ── After DOM ready ───────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Mobile hamburger + nav ──────────────────────────
    var hamburger = $('#hamburger');
    var nav = $('#main-nav');
    var overlay = $('#nav-overlay');

    function openMobileNav() {
      nav && nav.classList.add('open');
      overlay && overlay.classList.add('open');
      overlay && (overlay.style.display = 'block');
      hamburger && hamburger.setAttribute('aria-expanded', 'true');
    }
    function closeMobileNav() {
      nav && nav.classList.remove('open');
      overlay && overlay.classList.remove('open');
      overlay && (overlay.style.display = 'none');
      hamburger && hamburger.setAttribute('aria-expanded', 'false');
    }
    if (hamburger) hamburger.addEventListener('click', function () {
      nav && nav.classList.contains('open') ? closeMobileNav() : openMobileNav();
    });
    if (overlay) overlay.addEventListener('click', closeMobileNav);

    // ── 2. Admin sidebar toggle ────────────────────────────
    var sidebarToggle = $('#sidebar-toggle');
    var adminSidebar = $('.admin-sidebar');
    if (sidebarToggle && adminSidebar) {
      sidebarToggle.addEventListener('click', function () {
        adminSidebar.classList.toggle('open');
      });
    }

    // ── 3. Search bar toggle ───────────────────────────────
    var searchToggle = $('#search-toggle');
    var searchBar = $('#search-bar');
    var searchInput = searchBar && searchBar.querySelector('input');
    if (searchToggle && searchBar) {
      searchToggle.addEventListener('click', function () {
        var isOpen = searchBar.classList.toggle('open');
        searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        searchBar.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen && searchInput) { setTimeout(function () { searchInput.focus(); }, 150); }
      });
      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && searchBar.classList.contains('open')) {
          searchBar.classList.remove('open');
          searchToggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // ── 4. Flash auto-dismiss ─────────────────────────────
    var flash = $('#flash-banner');
    if (flash) {
      setTimeout(function () {
        flash.style.transition = 'opacity 0.5s ease, max-height 0.5s ease';
        flash.style.opacity = '0';
        flash.style.maxHeight = '0';
        flash.style.overflow = 'hidden';
        setTimeout(function () { flash.remove(); }, 600);
      }, 5000);
    }

    // ── 5. Confirm prompts ────────────────────────────────
    // Any element with data-confirm="Are you sure?" shows a confirm dialog
    $$('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        var msg = el.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });

    // ── 6. Image lazy-load placeholder ──────────────────
    $$('img[loading="lazy"]').forEach(function (img) {
      img.addEventListener('error', function () {
        this.style.background = 'linear-gradient(135deg, #1a2744, #111827)';
        this.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="50%" y="50%" font-size="40" text-anchor="middle" dy=".35em">📦</text></svg>';
      });
    });

    // ── 7. Active nav link highlight (by path) ───────────
    var path = window.location.pathname;
    $$('.nav__link:not([aria-haspopup])').forEach(function (link) {
      if (link.getAttribute('href') === path) {
        link.classList.add('nav__link--active');
      }
    });

    // ── 8. Smooth scroll for anchor links ────────────────
    $$('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var target = document.getElementById(this.getAttribute('href').slice(1));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      });
    });

    // ── 9. Header scroll shadow ───────────────────────────
    var header = $('#site-header');
    if (header) {
      window.addEventListener('scroll', function () {
        header.style.boxShadow = window.scrollY > 10 ? '0 4px 24px rgba(0,0,0,.4)' : 'none';
      }, { passive: true });
    }

    // ── 10. Cart quantity forms: auto-submit on blur ─────
    $$('.cart-qty input[type="number"]').forEach(function (input) {
      input.addEventListener('change', function () {
        var val = parseInt(this.value);
        var min = parseInt(this.min) || 1;
        var max = parseInt(this.max) || 99;
        if (val < min) this.value = min;
        if (val > max) this.value = max;
      });
    });

  }); // DOMContentLoaded

})();
